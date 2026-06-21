<?php

declare(strict_types=1);

class GigabyteSupportBridge extends BridgeAbstract
{
    const NAME = 'Gigabyte Support';
    const URI = 'https://www.gigabyte.com/';
    const DESCRIPTION = 'Returns BIOS and drivers updates for Gigabyte products';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 14400;
    const VALID_TYPES = ['driver', 'bios'];
    const PARAMETERS = [[
        'url' => [
            'name' => 'Support page URL',
            'type' => 'text',
            'required' => true,
            'title' => 'Full URL of the product support page on gigabyte.com (hash fragments like #Support-Bios or #Support-Driver are supported)'
        ],
        'hide_download_button' => [
            'name' => 'Hide download button',
            'type' => 'checkbox',
            'title' => 'Check this box to hide the download button from feed items'
        ]
    ]];
    private const CSS = [
        'item' => 'font-family:sans-serif;line-height:1.6;color:#333',
        'p' => 'margin:8px 0',
        'link' => 'color:#ff6600;text-decoration:none;font-weight:500',
        'label' => 'font-weight:bold;color:#111',
        'download' => 'display:inline-block;margin-top:10px;padding:8px 16px;background:#ff6600;color:#fff;text-decoration:none;border-radius:4px;font-weight:500',
    ];
    private $productInfo = null;
    private $pageContent = null;

    public function getIcon()
    {
        return 'https://www.gigabyte.com/favicon.ico';
    }

    public function getName()
    {
        $info = $this->getProductInfo();
        if (!$info) {
            return parent::getName();
        }
        $html = $this->fetchPageContent();
        $productName = $html ? $this->extractProductName($html) : null;
        $name = $productName ?? str_replace('-', ' ', $info['product']);
        $fragment = strtolower(preg_replace('/^support-/i', '', $info['fragment']));
        if (in_array($fragment, self::VALID_TYPES, true)) {
            $name .= ' (' . ($fragment === 'bios' ? 'BIOS' : ucfirst($fragment)) . ')';
        }
        return $name;
    }

    public function getURI()
    {
        return $this->getInput('url') ?: parent::getURI();
    }

    private function getProductInfo(): ?array
    {
        if ($this->productInfo !== null) {
            return $this->productInfo;
        }
        $url = $this->getInput('url');
        if (!$url) {
            return null;
        }
        $parsedUrl = parse_url($url);
        $segments = array_values(array_filter(explode('/', trim($parsedUrl['path'] ?? '', '/')), fn($s) => $s !== ''));
        if (count($segments) < 2) {
            return null;
        }
        if (end($segments) === 'support') {
            array_pop($segments);
        }
        $this->productInfo = [
            'product' => array_pop($segments),
            'category' => array_pop($segments),
            'fragment' => $parsedUrl['fragment'] ?? ''
        ];
        return $this->productInfo;
    }

    private function getDownloadTypes(): array
    {
        $fragment = strtolower(preg_replace('/^support-/i', '', $this->getProductInfo()['fragment'] ?? ''));
        return in_array($fragment, self::VALID_TYPES, true) ? [$fragment] : self::VALID_TYPES;
    }

    private function fetchPageContent(): ?string
    {
        if ($this->pageContent !== null) {
            return $this->pageContent;
        }
        $info = $this->getProductInfo();
        if (!$info) {
            return null;
        }
        try {
            $headers = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'User-Agent: Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36'
            ];
            $this->pageContent = getContents(sprintf('https://www.gigabyte.com/%s/%s/support', $info['category'], $info['product']), $headers);
            return $this->pageContent;
        } catch (Exception $e) {
            return null;
        }
    }

    private function extractProductName(string $html): ?string
    {
        if (!preg_match('/<[^>]*class="[^"]*model-base-info-title[^"]*"[^>]*>(.*?)<\/[^>]+>/is', $html, $match)) {
            return null;
        }
        $name = trim(strip_tags($match[1]));
        return !empty($name) ? $name : null;
    }

    private function normalize(string $text, bool $keepLinks = false): string
    {
        if (empty($text)) {
            return '';
        }
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/Checksum\s*:\s*\S+/i', '', $text);
        if ($keepLinks) {
            $text = preg_replace('/<li[^>]*>/i', '[[SEP]]', $text);
            $text = preg_replace('/<\/li>/i', '', $text);
            $text = preg_replace('/<br\s*\/?>/i', '[[SEP]]', $text);
            $text = preg_replace('/<\/?p[^>]*>/i', '[[SEP]]', $text);
            $text = preg_replace('/<\/?div[^>]*>/i', '[[SEP]]', $text);
            $linkStyle = self::CSS['link'];
            $text = preg_replace_callback('/<a\s+([^>]*?)>(.*?)<\/a>/is', function ($m) use ($linkStyle) {
                $attrs = $m[1];
                if (stripos($attrs, 'target=') === false) {
                    $attrs .= ' target="_blank"';
                }
                if (stripos($attrs, 'rel=') === false) {
                    $attrs .= ' rel="noopener noreferrer"';
                }
                if (stripos($attrs, 'style=') === false) {
                    $attrs .= ' style="' . $linkStyle . '"';
                }
                return '<a ' . trim($attrs) . '>' . $m[2] . '</a>';
            }, $text);
            $text = strip_tags($text);
            $parts = preg_split('/\[\[SEP\]\]|\n|\r\n?/', $text);
            $cleanParts = [];
            foreach ($parts as $part) {
                $part = preg_replace('/\s+/', ' ', trim($part));
                if (!empty($part)) {
                    $cleanParts[] = $part;
                }
            }
            return implode('<br>', $cleanParts);
        }
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/<br\s*\/?>/i', ', ', $text);
        $text = preg_replace('/(64bit|32bit)\s+(Windows|Linux|macOS)/i', '$1, $2', $text);
        $text = strip_tags($text);
        $text = preg_replace('/,\s*,/', ',', $text);
        return trim($text, ', ');
    }

    private function extractDownloadUrl(string $row): string
    {
        if (!preg_match('/href="([^"]*(?:download\.gigabyte\.com|\.zip)[^"]*)"/i', $row, $match)) {
            return '';
        }
        $url = $match[1];
        if (strpos($url, '/') === 0) {
            return 'https://www.gigabyte.com' . $url;
        }
        return $url;
    }

    private function parseTableRow(string $row, bool $hasOs): ?array
    {
        if (stripos($row, '<th') !== false) {
            return null;
        }
        preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $row, $matches);
        $cells = $matches[1];
        if (count($cells) < 4) {
            return null;
        }
        return [
            'description' => $this->normalize($cells[0], true),
            'version' => $this->normalize($cells[1]),
            'os' => $hasOs ? $this->normalize($cells[2]) : '',
            'size' => $this->normalize($cells[$hasOs ? 3 : 2]),
            'date' => $this->normalize($cells[$hasOs ? 4 : 3]),
            'download' => $this->extractDownloadUrl($row)
        ];
    }

    private function extractRowsFromTable(string $tableHtml): array
    {
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $tableHtml, $matches);
        $rows = [];
        $hasOs = null;
        foreach ($matches[1] as $row) {
            preg_match_all('/<td[^>]*>(.*?)<\/td>/is', $row, $cellMatches);
            if ($hasOs === null && count($cellMatches[1]) > 0) {
                $hasOs = count($cellMatches[1]) >= 6;
            }
            $parsed = $this->parseTableRow($row, $hasOs ?? false);
            if ($parsed !== null) {
                $rows[] = $parsed;
            }
        }
        return $rows;
    }

    private function parseSections(string $html): array
    {
        preg_match_all('/<h2[^>]*>([^<]+)<\/h2>\s*(?:<[^>]+>\s*)*<table[^>]*>(.*?)<\/table>/is', $html, $matches, PREG_SET_ORDER);
        $items = [];
        foreach ($matches as $match) {
            $category = trim($match[1]);
            $type = strtolower($category) === 'bios' ? 'bios' : 'driver';
            foreach ($this->extractRowsFromTable($match[2]) as $row) {
                $row['type'] = $type;
                $row['category'] = $category;
                $items[] = $row;
            }
        }
        return $items;
    }

    private function render(string $label, string $value, bool $lineBreak = false, bool $allowHtml = false): string
    {
        if (empty($value)) {
            return '';
        }
        $displayValue = $allowHtml ? $value : htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $displayValue = preg_replace('/^(?:\s|<br\s*\/?>)+/i', '', $displayValue);
        $displayValue = ltrim($displayValue);
        if (empty($displayValue)) {
            return '';
        }
        $separator = $lineBreak ? '<br>' : ' ';
        return sprintf('<p style="%s"><span style="%s">%s:</span>%s%s</p>', self::CSS['p'], self::CSS['label'], $label, $separator, $displayValue);
    }

    private function buildFeedItem(array $data, array $info, bool $hideAttachments): array
    {
        if ($data['type'] === 'bios') {
            $itemTitle = sprintf('[%s] %s', $data['category'], $data['version']);
        } else {
            $rawTitle = sprintf('[%s] %s', $data['category'], strip_tags($data['description']));
            $rawTitle = preg_replace('/Checksum\s*:\s*\S+/i', '', $rawTitle);
            $itemTitle = preg_replace('/\s+/', ' ', trim(trim($rawTitle)));
            if (!empty($data['version'])) {
                $itemTitle .= ' - ' . $data['version'];
            }
        }
        $uri = sprintf('https://www.gigabyte.com/%s/%s/support', $info['category'], $info['product']);
        if (!empty($info['fragment'])) {
            $uri .= '#' . $info['fragment'];
        }
        $content = '<div style="' . self::CSS['item'] . '">';
        $content .= $this->render('Description', $data['description'], true, true);
        $content .= $this->render('OS', $data['os']);
        $content .= $this->render('Size', $data['size']);
        if (!$hideAttachments && !empty($data['download'])) {
            $downloadUrl = htmlspecialchars($data['download'], ENT_QUOTES, 'UTF-8');
            $content .= sprintf(
                '<p style="%s"><a href="%s" style="%s" target="_blank" rel="noopener noreferrer">Download</a></p>',
                self::CSS['p'],
                $downloadUrl,
                self::CSS['download']
            );
        }
        $content .= '</div>';
        $item = ['title' => $itemTitle, 'uri' => $uri, 'content' => $content, 'uid' => md5($itemTitle . $data['version'])];
        if (!empty($data['date'])) {
            $timestamp = strtotime($data['date']);
            if ($timestamp !== false) {
                $item['timestamp'] = $timestamp;
            }
        }
        return $item;
    }

    public function collectData()
    {
        $info = $this->getProductInfo();
        if (!$info || !$info['product'] || !$info['category']) {
            throwClientException('Invalid URL format or could not extract product info. Expected format: https://www.gigabyte.com/Category/Product-ID/support');
        }
        $html = $this->fetchPageContent();
        if (!$html) {
            throwClientException('Failed to fetch support page content');
        }
        $hideAttachments = (bool) $this->getInput('hide_download_button');
        $types = $this->getDownloadTypes();
        $allItems = [];
        foreach ($this->parseSections($html) as $item) {
            if (in_array($item['type'], $types, true)) {
                $allItems[] = $this->buildFeedItem($item, $info, $hideAttachments);
            }
        }
        if (empty($allItems)) {
            return;
        }
        usort($allItems, fn($a, $b) => ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0));
        foreach ($allItems as $item) {
            $this->items[] = $item;
        }
    }
}