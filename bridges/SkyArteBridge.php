<?php

declare(strict_types=1);

class SkyArteBridge extends BridgeAbstract
{
    const NAME          = 'Sky Arte | Mostre ed eventi';
    const URI           = 'https://arte.sky.it';
    const MAINTAINER    = 'tillcash';
    const CACHE_TIMEOUT = 60 * 60 * 6; // 6 hour
    const MAX_ARTICLES  = 5;

    public function collectData()
    {
        $sitemapUrl = self::URI . '/sitemap-mostre-eventi.xml';
        $sitemapXml = getContents($sitemapUrl);
        if (!$sitemapXml) {
            throwServerException('Unable to fetch sitemap');
        }

        $sitemap = simplexml_load_string($sitemapXml, null, LIBXML_NOCDATA);
        if (!$sitemap) {
            throwServerException('Unable to parse sitemap');
        }

        $count = 0;
        foreach ($sitemap->url as $entry) {
            $url = trim((string) $entry->loc);
            if (!$url) {
                continue;
            }

            $json = $this->getJson($url);
            if (!$json) {
                continue;
            }

            $event = $this->parseEventData($json);

            $this->items[] = [
                'title'      => $event['title'],
                'uri'        => $url,
                'uid'        => $url,
                'timestamp'  => trim((string) $entry->lastmod),
                'content'    => $event['content'],
                'categories' => $event['categories'],
                'enclosures' => $event['enclosures'],
            ];

            if (++$count >= self::MAX_ARTICLES) {
                break;
            }
        }
    }

    private function getJson(string $url): ?array
    {
        $html = getSimpleHTMLDOMCached($url, 259200); // 3 days

        if (!$html) {
            return null;
        }

        $script = $html->find('script#__NEXT_DATA__', 0);
        if (!$script) {
            return null;
        }

        $decoded = json_decode($script->innertext, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function parseEventData(array $json): array
    {
        $props = $json['props']['pageProps']['data'] ?? [];
        $card  = $props['card'] ?? [];
        $info  = $props['info'] ?? [];

        $event = [
            'title'      => $card['title']['typography']['text'] ?? '(untitled)',
            'content'    => '',
            'categories' => [],
            'enclosures' => [],
        ];

        // Artist & Curators
        $artist = $info['artist']['text'] ?? '';
        $curators = [];
        if (!empty($info['curators']) && is_array($info['curators'])) {
            foreach ($info['curators'] as $c) {
                $curators[] = $c['text'] ?? '';
            }
        }

        // Location, Dates, Categories
        $location = '';
        $dates = '';
        if (!empty($card['informations']) && is_array($card['informations'])) {
            foreach ($card['informations'] as $block) {
                $icon = $block['iconRight']['Icon'] ?? '';
                if ($icon === 'SvgLocation') {
                    $location = $block['textRight']['text'] ?? '';
                }
                if ($icon === 'SvgEventEmpty') {
                    $dates = $block['textRight']['text'] ?? '';
                }
                if (!empty($block['badge']['label']['text'])) {
                    $event['categories'][] = $block['badge']['label']['text'];
                }
            }
        }

        // Enclosure
        if (!empty($card['image']['src'])) {
            $event['enclosures'][] = $card['image']['src'];
        }

        // HTML content
        $content = '';
        if ($artist) {
            $content .= '<p><strong>Artista:</strong> ' . htmlspecialchars($artist) . '</p>';
        }

        if ($curators) {
            $content .= '<p><strong>Curatori:</strong> ' . htmlspecialchars(implode(', ', $curators)) . '</p>';
        }

        if ($location) {
            $content .= '<p><strong>Luogo:</strong> ' . htmlspecialchars($location) . '</p>';
        }

        if ($dates) {
            $content .= '<p><strong>Periodo:</strong> ' . htmlspecialchars($dates) . '</p>';
        }

        $description = $props['description'] ?? '';
        if ($description) {
            $description = preg_replace('~<h2>(.*?)</h2>~i', '<strong>$1</strong>', $description);
            $description = nl2br($description);
            $content .= '<br><hr><br><p>' . $description . '</p>';
        }

        $event['content'] = $content;
        return $event;
    }
}
