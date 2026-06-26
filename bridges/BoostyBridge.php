<?php

declare(strict_types=1);

class BoostyBridge extends BridgeAbstract
{
    const NAME = 'Boosty';
    const URI = 'https://boosty.to';
    const DESCRIPTION = 'Parser for Boosty (free posts and paid announcements). No auth required';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [[
        'blog'     => ['name' => 'Blog', 'type' => 'text', 'required' => true, 'title' => 'Channel name, for example, rebel_jack from https://boosty.to/rebel_jack'],
        'limit'    => ['name' => 'Posts limit', 'type' => 'number', 'defaultValue' => 10],
        'hideTags' => ['name' => 'Hide tags', 'type' => 'checkbox', 'title' => 'Check this box to completely hide the tags list from the post content'],
    ]];

    private $blogName = '';
    private $blogAvatar = '';

    private const CSS = [
        'paywall'  => 'background:#f5f5f5;padding:15px;border-radius:5px;margin:10px 0',
        'pt'       => 'margin:0 0 10px 0;font-weight:bold',
        'pp'       => 'margin:5px 0',
        'poll'     => 'background:#f9f9f9;padding:15px;border-radius:5px;margin:10px 0;border-left:4px solid #4a90d9',
        'poll_t'   => 'margin:0 0 10px 0;font-weight:bold',
        'poll_o'   => 'margin:8px 0',
        'poll_r'   => 'display:flex;justify-content:space-between;margin-bottom:3px',
        'poll_m'   => 'color:#666;font-size:0.9em',
        'poll_f'   => 'margin:10px 0 0 0;color:#888;font-size:0.85em',
        'img'      => 'max-width:100%',
        'pg_table' => 'width:100%;height:8px;background:#e0e0e0;border-radius:3px;overflow:hidden',
        'pg_fill'  => 'background:#4a90d9;margin:0;padding:0;line-height:0;font-size:0',
        'pg_empty' => 'margin:0;padding:0;line-height:0;font-size:0',
    ];

    private const MEDIA = ['image' => 1, 'audio_file' => 1, 'file' => 1, 'ok_video' => 1];

    public function collectData(): void
    {
        $this->blogName = (string)$this->getInput('blog');
        foreach ($this->fetchPosts() as $p) {
            $item = $this->buildItem($p);
            if ($item) {
                $this->items[] = $item;
            }
        }
    }

    private function fetchPosts(): array
    {
        $limit = min((int)$this->getInput('limit') ?: 20, 100);
        $url = 'https://api.boosty.to/v1/blog/' . urlencode($this->blogName) . '/post/?limit=' . $limit;
        $data = Json::decode(getContents($url, [
            'Accept: application/json',
            'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
        ]));
        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new \Exception('Failed to fetch data from Boosty API');
        }
        if (!empty($data['data'][0]['user']['avatarUrl'])) {
            $this->blogAvatar = (string)$data['data'][0]['user']['avatarUrl'];
        }
        return $data['data'];
    }

    private function buildItem(array $p): ?array
    {
        if (!($p['isPublished'] ?? false)) {
            return null;
        }
        $paid = $this->isPaid($p);
        $title = ($paid ? '[Paid] ' : '') . ($p['title'] ?? 'Untitled');
        $content = $paid ? $this->renderPaywall($p) : $this->renderFree($p);
        return $this->meta($p, $title, $content);
    }

    private function isPaid(array $p): bool
    {
        return (isset($p['subscriptionLevel']) && $p['subscriptionLevel'] !== null) || (isset($p['price']) && $p['price'] > 0);
    }

    private function renderPaywall(array $p): string
    {
        $s = fn(string $k): string => $this->style($k);
        $h = '<div' . $s('paywall') . '><p' . $s('pt') . '>This post requires payment</p>';

        $teaser = $this->renderTeaser($p);
        if ($teaser !== '') {
            $h = $teaser . $h;
        }

        if (isset($p['subscriptionLevel'])) {
            $lv = $p['subscriptionLevel'];
            $h .= '<p' . $s('pp') . '><strong>Subscription:</strong> ' . $this->esc($lv['name'] ?? 'Unknown') . '</p>';
            $pr = $this->price($lv['currencyPrices'] ?? []);
            if ($pr) {
                $h .= '<p' . $s('pp') . '><strong>Price:</strong> ' . $this->esc($pr) . '/month</p>';
            }
        } elseif (isset($p['price']) && $p['price'] > 0) {
            $pr = $this->price($p['currencyPrices'] ?? [], $p['price']);
            if ($pr) {
                $h .= '<p' . $s('pp') . '><strong>Price:</strong> ' . $this->esc($pr) . '</p>';
            }
        }

        $postUrl = 'https://boosty.to/' . urlencode($this->blogName) . '/posts/' . urlencode($p['id'] ?? '');
        $h .= '<p' . $s('pp') . '><a href="' . $this->esc($postUrl) . '">View original post</a></p>';

        return $h . '</div>';
    }

    private function renderTeaser(array $p): string
    {
        $out = '';
        foreach ($p['teaser'] ?? [] as $b) {
            $type = $b['type'] ?? '';
            $rendition = $b['rendition'] ?? '';

            if ($type === 'image' && $rendition === 'teaser_auto_background') {
                continue;
            }

            if (isset(self::MEDIA[$type])) {
                $out .= $this->renderMedia($b);
            } elseif ($type === 'text') {
                $r = $this->draft($b['content'] ?? '');
                if ($r !== '') {
                    $out .= '<p>' . $r . '</p>';
                }
            } elseif ($type === 'link') {
                $r = $this->renderLink($b);
                if ($r !== '') {
                    $out .= '<p>' . $r . '</p>';
                }
            }
        }
        return $out;
    }

    private function meta(array $p, string $title, string $content): array
    {
        $item = [
            'uri'       => 'https://boosty.to/' . urlencode($this->blogName) . '/posts/' . urlencode($p['id'] ?? ''),
            'title'     => $title,
            'content'   => $content,
            'timestamp' => $p['publishTime'] ?? time(),
            'author'    => $p['user']['name'] ?? $this->blogName,
            'uid'       => $p['id'] ?? uniqid(),
        ];
        if (isset($p['tags']) && is_array($p['tags']) && !$this->getInput('hideTags')) {
            $item['categories'] = array_map(fn(array $t): string => $t['title'] ?? '', $p['tags']);
        }
        return $item;
    }

    private function renderFree(array $p): string
    {
        $out = '';
        $buf = [];
        foreach ($p['data'] ?? [] as $b) {
            $type = $b['type'] ?? '';
            $mod = $b['modificator'] ?? '';
            if ($type === 'text' && $mod === 'BLOCK_END') {
                if ($buf) {
                    $out .= '<p>' . implode('', $buf) . '</p>';
                    $buf = [];
                }
                continue;
            }
            if ($type === 'text') {
                $r = $this->draft($b['content'] ?? '');
                if ($r !== '') {
                    $buf[] = $r;
                }
            } elseif ($type === 'link') {
                $r = $this->renderLink($b);
                if ($r !== '') {
                    $buf[] = $r;
                }
            } else {
                if ($buf) {
                    $out .= '<p>' . implode('', $buf) . '</p>';
                    $buf = [];
                }
                if (isset(self::MEDIA[$type])) {
                    $out .= $this->renderMedia($b);
                }
            }
        }
        if ($buf) {
            $out .= '<p>' . implode('', $buf) . '</p>';
        }
        if (!empty($p['poll']) && is_array($p['poll'])) {
            $out .= $this->renderPoll($p['poll']);
        }
        return $out;
    }

    private function renderLink(array $b): string
    {
        $url = $b['url'] ?? '';
        if ($url === '') {
            return '';
        }
        $title = $this->draft($b['content'] ?? '');
        if ($title === '') {
            $title = $this->esc($url);
        }
        return '<a href="' . $this->esc($url) . '">' . $title . '</a>';
    }

    private function renderMedia(array $b): string
    {
        $type = $b['type'] ?? '';
        $url = $this->esc($b['url'] ?? ($b['preview'] ?? ($b['defaultPreview'] ?? '')));
        if ($url === '') {
            return '';
        }
        if ($type === 'image' || $type === 'ok_video') {
            return '<p><img src="' . $url . '"' . $this->style('img') . ' alt=""></p>';
        }
        $title = $this->esc($b['title'] ?? ($b['track'] ?? 'File'));
        if ($type === 'audio_file' && !empty($b['artist'])) {
            $title = $this->esc($b['artist']) . ' - ' . $title;
        }
        return '<p><a href="' . $url . '">' . $title . '</a></p>';
    }

    private function renderPoll(array $poll): string
    {
        $s = fn(string $k): string => $this->style($k);
        $h = '<div' . $s('poll') . '>';
        $title = $poll['title'] ?? '';
        if (is_array($title)) {
            $title = implode(' ', $title);
        }
        if ($title !== '') {
            $h .= '<p' . $s('poll_t') . '>Poll: ' . $this->esc($title) . '</p>';
        }
        $total = (int)($poll['counter'] ?? 0);
        if ($total === 0) {
            foreach ($poll['options'] ?? [] as $o) {
                $total += (int)($o['counter'] ?? 0);
            }
        }
        $vis = $this->pollVisible($poll, $total);
        foreach ($poll['options'] ?? [] as $o) {
            if ($vis) {
                $c = (int)($o['counter'] ?? 0);
                $f = isset($o['fraction']) ? (float)$o['fraction'] : ($total > 0 ? ($c / $total) * 100.0 : 0.0);
                $f = max(0.0, min(100.0, $f));
                $fd = rtrim(rtrim(number_format($f, 1, '.', ''), '0'), '.');
                $h .= '<div' . $s('poll_o') . '>';
                $h .= '<div' . $s('poll_r') . '>';
                $h .= '<span>' . $this->esc($o['text'] ?? '') . '</span>';
                $h .= '<span' . $s('poll_m') . '>' . $fd . '% (' . $c . ')</span>';
                $h .= '</div>';
                $h .= '<table cellpadding="0" cellspacing="0" border="0"' . $s('pg_table') . '>';
                $h .= '<tr>';
                $h .= '<td style="width:' . $f . '%;' . self::CSS['pg_fill'] . '"></td>';
                $h .= '<td' . $s('pg_empty') . '></td>';
                $h .= '</tr>';
                $h .= '</table>';
                $h .= '</div>';
            } else {
                $h .= '<div' . $s('poll_o') . '><span>' . $this->esc($o['text'] ?? '') . '</span></div>';
            }
        }
        $h .= '<p' . $s('poll_f') . '>Total votes: ' . $total;
        if (!empty($poll['isMultiple'])) {
            $h .= ' · Multiple choice';
        }
        if (!empty($poll['isFinished'])) {
            $h .= ' · Finished';
        }
        if (!$vis) {
            $h .= ' · Results hidden';
        }
        return $h . '</p></div>';
    }

    private function pollVisible(array $poll, int $total): bool
    {
        if (!empty($poll['isFinished']) || !empty($poll['showResults']) || !empty($poll['isResultVisible'])) {
            return true;
        }
        foreach ($poll['options'] ?? [] as $o) {
            if (array_key_exists('fraction', $o) || (isset($o['counter']) && $o['counter'] > 0)) {
                return true;
            }
        }
        return false;
    }

    private function draft(string $content): string
    {
        if ($content === '') {
            return '';
        }
        try {
            $d = json_decode($content, true);
            if (!is_array($d) || !isset($d[0])) {
                return '';
            }
            $text = $d[0];
            if (!is_string($text)) {
                $text = is_array($text) ? implode('', $text) : (string)$text;
            }
            if ($text === '') {
                return '';
            }
            $utf16 = mb_convert_encoding($text, 'UTF-16LE', 'UTF-8');
            $units = [];
            for ($i = 0, $len = strlen($utf16); $i < $len; $i += 2) {
                $units[] = substr($utf16, $i, 2);
            }
            $styles = (isset($d[2]) && is_array($d[2])) ? $d[2] : [];
            return str_replace("\n", '<br>', $this->applyStyles($units, $styles));
        } catch (\Exception $e) {
            return $this->esc($content);
        }
    }

    private function applyStyles(array $units, array $styles): string
    {
        $n = count($units);
        $tags = array_fill(0, $n + 1, '');
        $ev = [];
        foreach ($styles as $s) {
            if (count($s) < 3) {
                continue;
            }
            $tag = $this->tag((int)($s[0] ?? -1));
            if (!$tag) {
                continue;
            }
            $a = (int)($s[1] ?? 0);
            $b = $a + (int)($s[2] ?? 0);
            if ($a < 0 || $b > $n || $a >= $b) {
                continue;
            }
            $d = count($ev) / 2 + 1;
            $ev[] = ['p' => $a, 't' => "<{$tag}>", 'k' => 0, 'd' => $d];
            $ev[] = ['p' => $b, 't' => "</{$tag}>", 'k' => 1, 'd' => $d];
        }
        if ($ev) {
            usort($ev, function ($x, $y) {
                if ($x['p'] !== $y['p']) {
                    return $x['p'] - $y['p'];
                }
                if ($x['k'] !== $y['k']) {
                    return $x['k'] ? -1 : 1;
                }
                return $x['k'] ? $y['d'] - $x['d'] : $x['d'] - $y['d'];
            });
            foreach ($ev as $e) {
                $tags[$e['p']] .= $e['t'];
            }
        }
        $out = '';
        for ($i = 0; $i <= $n; $i++) {
            $out .= $tags[$i];
            if ($i < $n) {
                $hi = $i < $n - 1 ? (ord($units[$i][0]) | (ord($units[$i][1]) << 8)) : 0;
                if ($hi >= 0xD800 && $hi <= 0xDBFF) {
                    $out .= $this->esc(mb_convert_encoding($units[$i] . $units[$i + 1], 'UTF-8', 'UTF-16LE') ?: '');
                    $out .= $tags[++$i] ?? '';
                } else {
                    $out .= $this->esc(mb_convert_encoding($units[$i], 'UTF-8', 'UTF-16LE') ?: '');
                }
            }
        }
        return $out;
    }

    private function tag(int $type): ?string
    {
        return [0 => 'strong', 1 => 'u', 2 => 'em', 3 => 's'][$type] ?? null;
    }

    private function price(array $cp, $fb = null): ?string
    {
        if (isset($cp['RUB'])) {
            return $cp['RUB'] . ' RUB';
        }
        if (isset($cp['USD'])) {
            return $cp['USD'] . ' USD';
        }
        return $fb !== null ? (string)$fb : null;
    }

    private function esc($s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function style(string $key): string
    {
        return isset(self::CSS[$key]) ? ' style="' . self::CSS[$key] . '"' : '';
    }

    public function getName(): string
    {
        $blog = $this->getInput('blog');
        return $blog ? 'Boosty: ' . $blog : self::NAME;
    }

    public function getURI(): string
    {
        $blog = $this->getInput('blog');
        return $blog ? 'https://boosty.to/' . urlencode($blog) : self::URI;
    }

    public function getIcon(): string
    {
        return !empty($this->blogAvatar) ? $this->blogAvatar : parent::getIcon();
    }
}