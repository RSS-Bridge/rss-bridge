<?php

declare(strict_types=1);

class AuthorTodayBridge extends BridgeAbstract
{
    public const NAME = 'Author Today';
    public const URI = 'https://author.today';
    public const DESCRIPTION = 'Returns updates for stories by chapter';
    public const MAINTAINER = 'LordArrin';
    public const CACHE_TIMEOUT = 900;

    public const PARAMETERS = [
        '' => [
            'work' => [
                'name' => 'Story ID',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '230933',
            ],
            'notags' => [
                'name' => 'Disable tags',
                'type' => 'checkbox',
                'defaultValue' => false,
            ],
        ],
    ];

    public const HTTP_HEADERS = [
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) '
            . 'Chrome/149.0.0.0 Safari/537.36',
    ];

    public const CSS = [
        'status' => 'margin-bottom:10px',
        'label' => 'display:inline-block;padding:2px 6px;border-radius:3px;color:#ffffff;white-space:nowrap',
        'label_primary' => 'background:#337ab7',
        'label_success' => 'background:#5cb85c',
        'label_danger' => 'background:#d9534f',
        'label_default' => 'background:#777777',
        'meta' => 'white-space:nowrap',
        'separator' => 'color:#999999',
        'cover' => 'display:block;max-width:300px;height:auto;border-radius:4px',
    ];

    private const ITEM_LIMIT = 5;

    private string $feedTitle = '';

    public function getURI(): string
    {
        $workId = $this->workId();

        if ($workId === null) {
            return self::URI;
        }

        return self::URI . '/work/' . $workId;
    }

    public function getName(): string
    {
        return $this->feedTitle !== '' ? $this->feedTitle : self::NAME;
    }

    public function getIcon(): string
    {
        return self::URI . '/favicon.ico';
    }

    public function collectData(): void
    {
        $workId = $this->workId();

        if ($workId === null) {
            returnClientError('Story ID must be a number or a URL containing /work/{id}');
        }

        $url = self::URI . '/work/' . $workId;
        $html = getSimpleHTMLDOM($url, self::HTTP_HEADERS);

        if (!$html) {
            returnServerError('Unable to load page: ' . $url);
        }

        $titleNode = $html->find('h1.book-title', 0);
        $this->feedTitle = $titleNode ? trim($titleNode->plaintext) : '';

        $authorNode = $html->find('.book-authors a', 0);
        $author = $authorNode ? trim($authorNode->plaintext) : '';

        $coverNode = $html->find('img.cover-image', 0);
        $coverUrl = $coverNode ? $this->absoluteUrl((string)$coverNode->getAttribute('src')) : '';

        $statusHtml = $this->statusHtml($html);
        $tags = $this->getInput('notags') ? [] : $this->tags($html);
        $chapters = $html->find('#tab-chapters ul.table-of-content li');

        if (!$chapters) {
            returnServerError('Chapter list not found. The work may be unavailable or markup has changed.');
        }

        $items = [];

        foreach (array_reverse($chapters) as $position => $chapter) {
            $link = $chapter->find('a', 0);

            if (!$link) {
                continue;
            }

            $title = trim($link->plaintext);
            $uri = $this->absoluteUrl((string)$link->getAttribute('href'));
            $timeNode = $chapter->find('[data-time]', 0);
            $timestamp = $timeNode ? $this->timestamp((string)$timeNode->getAttribute('data-time')) : null;

            $content = $statusHtml;

            if ($coverUrl !== '') {
                $content .= '<p><a href="' . htmlspecialchars($uri, ENT_QUOTES, 'UTF-8') . '">'
                    . '<img src="' . htmlspecialchars($coverUrl, ENT_QUOTES, 'UTF-8')
                    . '" alt="Cover" style="' . self::CSS['cover'] . '"></a></p>';
            }

            $item = [
                'uri' => $uri,
                'title' => $title !== '' ? $title : 'Chapter',
                'uid' => $uri,
                'content' => $content,
                '_position' => $position,
            ];

            if ($timestamp !== null) {
                $item['timestamp'] = $timestamp;
            }

            if ($author !== '') {
                $item['author'] = $author;
            }

            if ($tags !== []) {
                $item['categories'] = $tags;
            }

            $items[] = $item;
        }

        if (!$items) {
            returnServerError('Unable to parse chapters.');
        }

        usort($items, static function (array $a, array $b): int {
            $time = ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);

            if ($time !== 0) {
                return $time;
            }

            return $a['_position'] <=> $b['_position'];
        });

        foreach (array_slice($items, 0, self::ITEM_LIMIT) as $item) {
            unset($item['_position']);
            $this->items[] = $item;
        }
    }

    private function workId(): ?string
    {
        $value = trim((string)$this->getInput('work'));

        if ($value === '') {
            return null;
        }

        if (ctype_digit($value)) {
            return $value;
        }

        if (preg_match('#^(\d+)/?$#', $value, $matches)) {
            return $matches[1];
        }

        if (preg_match('#/work/(\d+)#', $value, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function absoluteUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return self::URI;
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return self::URI . '/' . ltrim($url, '/');
    }

    private function timestamp(string $value): ?int
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = (string)preg_replace('/\.\d+/', '', $value);

        if (!preg_match('/(Z|[+-]\d{2}:?\d{2})$/', $value)) {
            $value .= 'Z';
        }

        $date = date_create($value);

        if ($date === false) {
            return null;
        }

        return $date->getTimestamp();
    }

    private function plainText($node): string
    {
        if ($node === null) {
            return '';
        }

        $text = html_entity_decode(strip_tags($node->innertext), ENT_QUOTES, 'UTF-8');

        return trim((string)preg_replace('/\s+/u', ' ', $text));
    }

    private function tags($html): array
    {
        $tags = [];

        foreach ($html->find('.mb-v-lg .tags a') as $node) {
            $tag = $this->plainText($node);

            if ($tag !== '') {
                $tags[] = $tag;
            }
        }

        return array_values(array_unique($tags));
    }

    private function statusIcon(string $class): string
    {
        if (strpos($class, 'icon-pencil') !== false) {
            return '&#9998;';
        }

        if (strpos($class, 'icon-check') !== false) {
            return '&#10004;';
        }

        return '&#8226;';
    }

    private function labelStyle(string $class): string
    {
        $color = self::CSS['label_default'];

        if (strpos($class, 'label-success') !== false) {
            $color = self::CSS['label_success'];
        } elseif (strpos($class, 'label-primary') !== false) {
            $color = self::CSS['label_primary'];
        } elseif (strpos($class, 'label-danger') !== false) {
            $color = self::CSS['label_danger'];
        }

        return self::CSS['label'] . ';' . $color;
    }

    private function isInsideFooter($node): bool
    {
        for ($i = 0; $i < 8 && $node !== null; $i++) {
            if (strtolower((string)$node->tag) === 'footer') {
                return true;
            }

            $node = $node->parent();
        }

        return false;
    }

    private function adultText($html): string
    {
        foreach ($html->find('.label-adult-only') as $node) {
            if ($this->isInsideFooter($node)) {
                continue;
            }

            $text = $this->plainText($node);

            if ($text !== '') {
                return $text;
            }
        }

        return '';
    }

    private function likeCount($html): string
    {
        $source = (string)$html->save();

        if (preg_match('/likeCount["\']?\s*:\s*["\']?(\d+)/i', $source, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function statusHtml($html): string
    {
        $label = $html->find('.book-meta-panel .label', 0);
        $time = $html->find('.book-meta-panel [data-format="calendar-short"]', 0);

        if ($label === null && $time === null) {
            return '';
        }

        $parts = [];
        $labelText = $this->plainText($label);

        if ($labelText !== '') {
            $iconNode = $label->find('i', 0);
            $iconClass = $iconNode ? (string)$iconNode->getAttribute('class') : '';
            $labelClass = (string)$label->getAttribute('class');

            $parts[] = '<span style="' . $this->labelStyle($labelClass) . '">'
                . $this->statusIcon($iconClass) . '&#160;'
                . htmlspecialchars($labelText, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        $adultText = $this->adultText($html);

        if ($adultText !== '') {
            $parts[] = '<span style="' . $this->labelStyle('label-danger') . '">'
                . htmlspecialchars($adultText, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        $likes = $this->likeCount($html);

        if ($likes !== '') {
            $parts[] = '<span style="' . self::CSS['meta'] . '">&#9829;&#160;'
                . htmlspecialchars($likes, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        if ($time !== null) {
            $timestamp = $this->timestamp((string)$time->getAttribute('data-time'));

            if ($timestamp !== null) {
                $parts[] = '<span>'
                    . htmlspecialchars(date('d.m.Y H:i', $timestamp), ENT_QUOTES, 'UTF-8')
                    . '</span>';
            }
        }

        $statusNode = $label ? $label->parent() : null;
        $sizeText = $this->plainText($statusNode);

        if ($sizeText !== '' && $labelText !== '') {
            $sizeText = trim(str_replace($labelText, '', $sizeText), " \t\n\r\0\x0B|");
        }

        if ($sizeText !== '' && $adultText !== '') {
            $sizeText = trim(str_replace($adultText, '', $sizeText), " \t\n\r\0\x0B|");
        }

        if ($sizeText !== '') {
            $parts[] = '<span>' . htmlspecialchars($sizeText, ENT_QUOTES, 'UTF-8') . '</span>';
        }

        if (!$parts) {
            return '';
        }

        $separator = '<span style="' . self::CSS['separator'] . '">&#160;|&#160;</span>';

        return '<div style="' . self::CSS['status'] . '">' . implode($separator, $parts) . '</div>';
    }
}