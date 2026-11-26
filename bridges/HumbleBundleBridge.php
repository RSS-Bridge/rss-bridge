<?php

class HumbleBundleBridge extends BridgeAbstract
{
    const NAME = 'Humble Bundle';
    const MAINTAINER = 'phantop';
    const URI = 'https://www.humblebundle.com/';
    const DESCRIPTION = 'Returns bundles from Humble Bundle.';
    const PARAMETERS = [[
        'type' => [
            'name' => 'Bundle type',
            'type' => 'list',
            'defaultValue' => 'bundles',
            'values' => [
                'All' => 'bundles',
                'Books' => 'books',
                'Games' => 'games',
                'Software' => 'software',
                ]
            ]
    ]];

    public function collectData()
    {
        $rows = $this->readRows($this->getURI());
        $recognized = false;
        $seen = [];
        foreach ($this->objectsWith($rows, 'feedItems') as $section) {
            if (($section['surface'] ?? null) !== 'bundles-listing' || !is_array($section['feedItems'])) {
                continue;
            }
            if (!$section['feedItems']) {
                $recognized = true;
            }
            foreach ($section['feedItems'] as $entry) {
                $product = $entry['value'] ?? [];
                $path = $product['url'] ?? '';
                if (!preg_match('~^/(books|games|software)/[a-zA-Z0-9_-]+$~D', $path, $matches)) {
                    continue;
                }
                if (empty($product['title'])) {
                    continue;
                }
                $recognized = true;
                $type = $this->getInput('type');
                if ($type !== 'bundles' && $type !== $matches[1]) {
                    continue;
                }
                $uid = $product['productKey'] ?? $path;
                if (isset($seen[$uid])) {
                    continue;
                }
                $seen[$uid] = true;
                $uri = rtrim(self::URI, '/') . $path;
                $detail = $this->loadCacheValue($uri);
                if ($detail === null) {
                    try {
                        $detail = $this->bundleDetail($this->readRows($uri));
                        if ($detail) {
                            $this->saveCacheValue($uri, $detail, $this->getCacheTimeout());
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning($e->getMessage());
                    }
                }
                $content = $this->image($product['thumbnail'] ?? null) . ($detail['content'] ?? '');
                if (empty($detail['hasItems']) && !empty($product['includedItemTitles'])) {
                    $content .= '<ul>';
                    foreach ($product['includedItemTitles'] as $title) {
                        $content .= '<li>' . htmlspecialchars($title) . '</li>';
                    }
                    $content .= '</ul>';
                }
                $this->items[] = [
                    'uid' => $uid,
                    'uri' => $uri,
                    'title' => $product['title'],
                    'categories' => [$matches[1]],
                    'timestamp' => $detail['timestamp'] ?? null,
                    'content' => $content,
                ];
            }
        }
        if (!$recognized) {
            throw new \Exception('Unable to find the Humble Bundle listing');
        }
    }

    private function image($url): string
    {
        if (!is_string($url) || strpos($url, 'https://') !== 0) {
            return '';
        }
        return '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="">';
    }

    protected function fetchPage(string $uri): string
    {
        return getContents($uri);
    }

    // Next.js embeds a stream of newline-delimited JSON records and byte-counted text records.
    private function readRows(string $uri): array
    {
        $html = $this->fetchPage($uri);
        $stream = '';
        $prefix = 'self.__next_f.push(';
        $offset = 0;
        while (($start = strpos($html, $prefix, $offset)) !== false) {
            $start += strlen($prefix);
            $end = strpos($html, '</script>', $start);
            if ($end === false) {
                break;
            }
            $chunk = json_decode(rtrim(substr($html, $start, $end - $start), "); \t\r\n"), true);
            if (($chunk[0] ?? null) === 1 && is_string($chunk[1] ?? null)) {
                $stream .= $chunk[1];
            }
            $offset = $end + 9;
        }

        $rows = [];
        $offset = 0;
        while (preg_match('/\G([a-f0-9]*):(?:T([a-f0-9]+),)?/', $stream, $m, 0, $offset)) {
            $offset += strlen($m[0]);
            if (isset($m[2])) {
                $length = hexdec($m[2]);
                if ($length > strlen($stream) - $offset) {
                    break;
                }
                $rows[$m[1]] = substr($stream, $offset, $length);
                $offset += $length;
            } else {
                $end = strpos($stream, "\n", $offset);
                if ($end === false) {
                    break;
                }
                $row = json_decode(substr($stream, $offset, $end - $offset), true);
                if ($row !== null) {
                    $rows[$m[1]] = $row;
                }
                $offset = $end + 1;
            }
        }
        return $rows;
    }

    private function objectsWith(array $tree, string $key): iterable
    {
        if (array_key_exists($key, $tree)) {
            yield $tree;
            return;
        }
        foreach ($tree as $child) {
            if (is_array($child)) {
                yield from $this->objectsWith($child, $key);
            }
        }
    }

    private function description($text, array $rows): string
    {
        if (is_string($text) && preg_match('/^\$([a-f0-9]+)$/D', $text, $m)) {
            $text = $rows[$m[1]] ?? '';
        }
        if (!is_string($text) || $text === '' || $text === '$undefined') {
            return '';
        }
        return '<p>' . htmlspecialchars(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')) . '</p>';
    }

    private function bundleDetail(array $rows): array
    {
        foreach ($this->objectsWith($rows, 'bundlePage') as $props) {
            $bundle = $props['bundlePage'];
            if (!is_array($bundle)) {
                continue;
            }
            $content = $this->description($bundle['description'] ?? $bundle['headline'] ?? null, $rows);
            $seen = [];
            foreach ($bundle['tiers'] ?? [] as $index => $tier) {
                $price = $tier['price'] ?? [];
                $heading = 'Tier ' . ($index + 1);
                if (isset($price['amount'], $price['currencyCode'])) {
                    $heading = $price['amount'] . ' ' . $price['currencyCode'];
                }
                $content .= '<h2>' . htmlspecialchars($heading) . '</h2>';
                foreach ($tier['bundleItems'] ?? [] as $item) {
                    // Later tiers refer back to items already described in earlier tiers.
                    if (!is_array($item) || empty($item['title'])) {
                        continue;
                    }
                    $id = $item['key'] ?? $item['title'];
                    if (!isset($seen[$id])) {
                        $seen[$id] = true;
                        $content .= '<h3>' . htmlspecialchars($item['title']) . '</h3>';
                        $content .= $this->image($item['thumbnailImageUrl'] ?? null);
                        $content .= $this->description($item['description'] ?? null, $rows);
                    }
                }
            }
            return ['content' => $content, 'hasItems' => (bool)$seen, 'timestamp' => $bundle['startDate'] ?? null];
        }
        return [];
    }

    public function getName()
    {
        $name = parent::getName();
        $name .= $this->getInput('type') ? ' - ' . $this->getInput('type') : '';
        return $name;
    }

    public function getURI()
    {
        $uri = parent::getURI() . $this->getInput('type');
        return $uri;
    }
}
