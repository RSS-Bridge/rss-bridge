<?php

declare(strict_types=1);

class RuStoreBridge extends BridgeAbstract
{
    const NAME = 'RuStore';
    const URI = 'https://www.rustore.ru';
    const DESCRIPTION = 'Returns application updates with its changelog';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [
        [
            'package' => [
                'name' => 'Package name',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'com.flyersoft.moonreader',
            ]
        ]
    ];

    const BASE_URL = 'https://www.rustore.ru/catalog/app/';

    const CSS = [
        'item'  => 'background:#f9f9f9;padding:12px;border-left:3px solid #0077ff;margin:0 0 15px 0;font-size:0.95em;line-height:1.5',
        'empty' => 'font-style:italic;color:#999',
    ];

    const HTTP_HEADERS = [
        'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36',
        'Accept: text/html,application/xhtml+xml,application/xml',
        'Referer: https://www.rustore.ru/',
    ];

    private $appName = '';
    private $appIcon = null;

    public function collectData()
    {
        $package = $this->getInput('package');

        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*(\.[a-zA-Z][a-zA-Z0-9_]*)+$/', $package)) {
            throw new \Exception('Invalid package name format.');
        }

        $url = self::BASE_URL . urlencode($package) . '/versions';
        $html = getSimpleHTMLDOM($url, self::HTTP_HEADERS);

        if (!$html) {
            throw new \Exception('Failed to load page: ' . $url);
        }

        $this->extractMeta($html);
        $versions = $this->parseJsonLd($html) ?: $this->parseNextJsPayload($html);

        if (empty($versions)) {
            throw new \Exception('No version data found on the page.');
        }

        foreach ($versions as $version) {
            $this->items[] = $this->buildItem($version, $package);
        }
    }

    public function getName()
    {
        return $this->appName ?: parent::getName();
    }

    public function getURI()
    {
        return $this->getInput('package') ? self::BASE_URL . urlencode($this->getInput('package')) : parent::getURI();
    }

    public function getIcon()
    {
        return $this->appIcon ?: parent::getIcon();
    }

    private function extractMeta($html)
    {
        foreach ($html->find('script[type="application/ld+json"]') as $script) {
            $data = json_decode(trim($script->innertext), true);
            if (!is_array($data)) {
                continue;
            }

            if (($data['@type'] ?? '') === 'BreadcrumbList' && !empty($data['itemListElement'])) {
                $last = end($data['itemListElement']);
                $this->appName = $last['name'] ?? '';
                break;
            }
        }

        $og = $html->find('meta[property="og:image"]', 0);
        if ($og && !empty($og->content)) {
            $this->appIcon = strpos($og->content, '//') === 0 ? 'https:' . $og->content : $og->content;
        }
    }

    private function parseJsonLd($html)
    {
        $versions = [];

        foreach ($html->find('script[type="application/ld+json"]') as $script) {
            $data = json_decode(trim($script->innertext), true);
            if (!is_array($data) || ($data['@type'] ?? '') !== 'ItemList') {
                continue;
            }

            foreach ($data['itemListElement'] ?? [] as $element) {
                if (($element['@type'] ?? '') !== 'UpdateAction' || empty($element['name'])) {
                    continue;
                }

                $versions[] = [
                    'versionName' => $element['name'],
                    'whatsNew'    => $element['description'] ?? '',
                    'date'        => $element['startTime'] ?? '',
                ];
            }

            if (!empty($versions)) {
                break;
            }
        }

        return $versions;
    }

    private function parseNextJsPayload($html)
    {
        $allContent = '';
        foreach ($html->find('script') as $script) {
            if (strpos($script->innertext, 'self.__next_f.push') !== false) {
                $allContent .= $script->innertext . "\n";
            }
        }

        if (empty($allContent)) {
            return [];
        }

        if (preg_match('/"versions"\s*:\s*\[(.+?)\]\s*,\s*"continuation"/s', $allContent, $match)) {
            $json = stripcslashes('[' . $match[1] . ']');
            $data = json_decode($json, true);

            if (!is_array($data)) {
                return [];
            }

            $versions = [];
            foreach ($data as $v) {
                if (empty($v['versionName'])) {
                    continue;
                }
                $versions[] = [
                    'versionName' => $v['versionName'],
                    'whatsNew'    => $v['whatsNew'] ?? '',
                    'date'        => $v['appVerUpdatedAt'] ?? '',
                ];
            }
            return $versions;
        }

        return [];
    }

    private function looksLikeImplicitList($lines)
    {
        if (count($lines) < 2) {
            return false;
        }

        $semicolonCount = 0;
        $capitalStartCount = 0;
        $hasExplicitMarker = false;

        foreach ($lines as $line) {
            if (preg_match('/;\s*$/u', $line)) {
                $semicolonCount++;
            }
            if (preg_match('/^[\p{Lu}0-9]/u', $line)) {
                $capitalStartCount++;
            }
            if (preg_match('/^[\x{00AD}\x{FEFF}]?[\x{2014}\x{2013}\-\x{2022}\x{25CF}*]/u', $line)) {
                $hasExplicitMarker = true;
            }
        }

        if ($hasExplicitMarker) {
            return false;
        }

        if ($semicolonCount >= count($lines) - 1) {
            return true;
        }

        return $capitalStartCount >= count($lines);
    }

    private function formatChangelogHtml($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return '<p style="' . self::CSS['empty'] . '">No changelog available for this version.</p>';
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $lines = array_values(array_filter(array_map('trim', $lines)));

        if (empty($lines)) {
            return '<p style="' . self::CSS['empty'] . '">No changelog available for this version.</p>';
        }

        $bullet = "\u{2022}";
        $processed = [];
        $isImplicitList = $this->looksLikeImplicitList($lines);

        foreach ($lines as $line) {
            if (preg_match('/^[\x{00AD}\x{FEFF}]?([\x{2460}-\x{2473}])\s*(.*)$/u', $line, $m)) {
                $num = mb_ord($m[1], 'UTF-8') - 0x245F;
                $clean = trim($m[2]);
                if ($clean !== '') {
                    $clean = mb_strtoupper(mb_substr($clean, 0, 1)) . mb_substr($clean, 1);
                    $processed[] = ['type' => 'numbered', 'num' => $num, 'text' => $clean];
                }
                continue;
            }

            if (preg_match('/^[\x{00AD}\x{FEFF}]?\x{25CF}\s*(.*)$/u', $line, $m)) {
                $clean = trim($m[1]);
                if ($clean !== '') {
                    $clean = mb_strtoupper(mb_substr($clean, 0, 1)) . mb_substr($clean, 1);
                    $processed[] = ['type' => 'bullet', 'text' => $clean];
                }
                continue;
            }

            if (preg_match('/^[\x{00AD}\x{FEFF}]?[\x{2014}\x{2013}\-\x{2022}*]\s*(.*)$/u', $line, $m)) {
                $clean = trim($m[1]);
                if ($clean !== '') {
                    $clean = mb_strtoupper(mb_substr($clean, 0, 1)) . mb_substr($clean, 1);
                    $processed[] = ['type' => 'bullet', 'text' => $clean];
                }
                continue;
            }

            if ($line !== '') {
                if ($isImplicitList) {
                    $clean = rtrim($line, '; ');
                    $clean = mb_strtoupper(mb_substr($clean, 0, 1)) . mb_substr($clean, 1);
                    $processed[] = ['type' => 'bullet', 'text' => $clean];
                } else {
                    $processed[] = ['type' => 'plain', 'text' => $line];
                }
            }
        }

        if (empty($processed)) {
            return '<p style="' . self::CSS['empty'] . '">No changelog available for this version.</p>';
        }

        $formatted = [];
        foreach ($processed as $item) {
            $escaped = htmlspecialchars($item['text']);
            if ($item['type'] === 'plain') {
                $formatted[] = $escaped;
            } elseif (count($processed) === 1) {
                $formatted[] = $escaped;
            } elseif ($item['type'] === 'numbered') {
                $formatted[] = $item['num'] . '. ' . $escaped;
            } else {
                $formatted[] = $bullet . ' ' . $escaped;
            }
        }

        return '<div style="' . self::CSS['item'] . '"><p>'
             . implode('<br>', $formatted)
             . '</p></div>';
    }

    private function buildItem($version, $package)
    {
        $versionName = $version['versionName'];
        $content = $this->formatChangelogHtml($version['whatsNew'] ?? '');

        $timestamp = time();
        if (!empty($version['date'])) {
            $ts = strtotime($version['date']);
            if ($ts !== false) {
                $timestamp = $ts;
            }
        }

        return [
            'uri'       => self::BASE_URL . urlencode($package) . '/versions#v' . urlencode($versionName),
            'title'     => $versionName,
            'content'   => $content,
            'uid'       => 'rustore-' . $package . '-' . $versionName,
            'timestamp' => $timestamp,
        ];
    }
}