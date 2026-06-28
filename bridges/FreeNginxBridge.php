<?php

declare(strict_types=1);

class FreeNginxBridge extends BridgeAbstract
{
    const NAME = 'FreeNginx';
    const URI = 'https://freenginx.org/';
    const DESCRIPTION = 'Returns FreeNginx releases with changelogs and other news';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        [
            'source' => [
                'name' => 'Source',
                'type' => 'list',
                'values' => [
                    'Releases' => 'changes',
                    'News' => 'news'
                ],
                'defaultValue' => 'changes'
            ],
            'limit' => [
                'name' => 'Number of entries (max 20)',
                'type' => 'number',
                'defaultValue' => 5
            ]
        ]
    ];

    private const CSS = [
        'wrapper' => 'font-size:14px; line-height:1.6; word-wrap:break-word;',
        'section' => 'margin:12px 0;',
        'heading' => 'margin:0 0 8px 0; font-size:16px; padding-left:12px; border-left:4px solid %s;',
        'ul' => 'list-style-type:disc; padding-left:24px;',
        'colors' => [
            'Security' => '#cf222e',
            'Feature' => '#1a7f37',
            'Bugfix' => '#0969da',
            'Change' => '#9a6700',
            'Other' => '#6e7781',
        ],
    ];

    private const BASE_URL = 'https://freenginx.org';

    private const PLURALS = [
        'Change' => 'Changes',
        'Feature' => 'Features',
        'Bugfix' => 'Bugfixes',
    ];

    public function collectData()
    {
        $source = $this->getInput('source');
        $limit = (int)$this->getInput('limit') ?: 20;

        if ($source === 'changes') {
            $this->collectChanges();
        } elseif ($source === 'news') {
            $this->collectNews();
        }

        usort($this->items, fn($a, $b) => $b['timestamp'] - $a['timestamp']);
        $this->items = array_slice($this->items, 0, $limit);
    }

    private function collectChanges()
    {
        $content = getContents('https://freenginx.org/en/CHANGES');
        if (!$content) {
            throw new \Exception('Failed to load CHANGES');
        }

        $blocks = preg_split('/(?=Changes with freenginx\s+)/', $content);

        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) {
                continue;
            }

            if (!preg_match('/^Changes with freenginx\s+(\S+)\s+(\d{1,2}\s+\w+\s+\d{4})/', $block, $m)) {
                continue;
            }

            $version = $m[1];
            $changes = preg_replace('/^Changes with freenginx\s+\S+\s+\d{1,2}\s+\w+\s+\d{4}\s*/', '', $block);

            $items = $this->parseChangeItems($changes);
            $categories = $this->groupItemsByCategory($items);
            $html = $this->buildCategoriesHtml($categories);

            $this->items[] = [
                'uri' => 'https://freenginx.org/en/CHANGES',
                'title' => "freenginx {$version}",
                'content' => $html,
                'timestamp' => strtotime($m[2]),
                'uid' => 'freenginx-' . $version,
            ];
        }
    }

    private function parseChangeItems(string $changes): array
    {
        $lines = explode("\n", $changes);
        $items = [];
        $current = '';

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (empty($trimmed)) {
                continue;
            }

            if (strpos($trimmed, '*) ') === 0) {
                if ($current) {
                    $items[] = $this->normalizeItem($current);
                }
                $current = substr($trimmed, 3);
            } else {
                $current .= ' ' . $trimmed;
            }
        }

        if ($current) {
            $items[] = $this->normalizeItem($current);
        }

        return $items;
    }

    private function normalizeItem(string $item): string
    {
        $item = preg_replace('/Thanks to [^.]+\./', '', $item);
        $item = preg_replace('/\s+/', ' ', trim($item));

        if (preg_match('/^(\w+):\s*(.+)$/', $item, $m)) {
            return $m[1] . ': ' . $this->capitalizeFirst($m[2]);
        }

        return $this->capitalizeFirst($item);
    }

    private function capitalizeFirst(string $text): string
    {
        $text = trim($text);
        if (empty($text)) {
            return $text;
        }
        return mb_strtoupper(mb_substr($text, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($text, 1, null, 'UTF-8');
    }

    private function groupItemsByCategory(array $items): array
    {
        $categories = [];
        foreach ($items as $item) {
            if (preg_match('/^(\w+):\s*/', $item, $m)) {
                $categories[$m[1]][] = preg_replace('/^\w+:\s*/', '', $item);
            } else {
                $categories['Other'][] = $item;
            }
        }
        return $categories;
    }

    private function buildCategoriesHtml(array $categories): string
    {
        $html = sprintf('<div style="%s">', self::CSS['wrapper']);

        foreach ($categories as $category => $items) {
            $color = self::CSS['colors'][$category] ?? self::CSS['colors']['Other'];
            $label = $this->getPluralCategory($category, count($items));

            $html .= sprintf('<div style="%s">', self::CSS['section']);
            $html .= sprintf('<h3 style="%s">%s</h3>', sprintf(self::CSS['heading'], $color), htmlspecialchars($label));
            $html .= sprintf('<ul style="%s">', self::CSS['ul']);

            foreach ($items as $item) {
                $html .= '<li>' . htmlspecialchars($item) . '</li>';
            }

            $html .= '</ul></div>';
        }

        return $html . '</div>';
    }

    private function getPluralCategory(string $category, int $count): string
    {
        if ($category === 'Security' || $count === 1) {
            return $category;
        }

        return self::PLURALS[$category] ?? $category . 's';
    }

    private function collectNews()
    {
        $html = getSimpleHTMLDOM('https://freenginx.org/');
        if (!$html) {
            throw new \Exception('Failed to load homepage');
        }

        $newsTable = $html->find('table', 0);
        if (!$newsTable) {
            throw new \Exception('News table not found');
        }

        foreach ($newsTable->find('tr') as $row) {
            $cells = $row->find('td');
            if (count($cells) < 2) {
                continue;
            }

            $dateText = trim($cells[0]->plaintext);
            $content = trim($cells[1]->innertext);

            if (empty($dateText) || empty($content)) {
                continue;
            }

            $dom = str_get_html($content);
            if ($dom) {
                $dom = defaultLinkTo($dom, self::BASE_URL);
                $content = $dom->save();
            }

            $this->items[] = [
                'uri' => 'https://freenginx.org/',
                'title' => 'Update ' . $dateText,
                'content' => sprintf('<div style="%s">%s</div>', self::CSS['wrapper'], $content),
                'timestamp' => strtotime($dateText),
                'uid' => 'freenginx-news-' . $dateText,
            ];
        }
    }

    public function getName()
    {
        $source = $this->getInput('source');
        return $source === 'news' ? 'Freenginx News' : 'Freenginx Releases';
    }

    public function getURI()
    {
        return 'https://freenginx.org/';
    }
}