<?php

declare(strict_types=1);

class ThePintBridge extends BridgeAbstract
{
    const NAME = 'The Pint';
    const URI = 'https://www.thepint.news/';
    const DESCRIPTION = 'The Pint archive posts';
    const MAINTAINER = 'jacob';
    const CACHE_TIMEOUT = 1800;

    const PARAMETERS = [[
        'fulltext' => [
            'name' => 'Fetch full article content',
            'type' => 'checkbox',
            'required' => false,
        ],
        'limit' => [
            'name' => 'Max items',
            'type' => 'number',
            'required' => false,
            'defaultValue' => 10,
        ],
    ]];

    public function collectData()
    {
        $fulltext = (bool)$this->getInput('fulltext');
        $limit = (int)($this->getInput('limit') ?: 10);

        if ($limit < 1 || $limit > 50) {
            throwClientException('Limit must be between 1 and 50');
        }

        $archiveUrl = self::URI . 'archive';
        $dom = getSimpleHTMLDOM($archiveUrl);

        $urls = [];
        foreach ($dom->find('a[href*="/p/"], a[href^="p/"]') as $a) {
            $href = trim((string)$a->href);
            if ($href === '') {
                continue;
            }

            $url = urljoin(self::URI, $href);

            if (!preg_match('#/p/[a-z0-9\\-]+#i', $url)) {
                continue;
            }

            $urls[$url] = true;
        }

        $articleUrls = array_slice(array_keys($urls), 0, $limit);

        if (count($articleUrls) === 0) {
            throwServerException('No article links found on archive page');
        }

        foreach ($articleUrls as $url) {
            $this->items[] = $this->buildItemFromArticle($url, $fulltext);
        }
    }

    private function buildItemFromArticle(string $url, bool $fulltext): array
    {
        $html = getContents($url);

        if (!$html || trim($html) === '') {
            throwServerException('Empty response from article page: ' . $url);
        }

        $item = [
            'uid' => $url,
            'uri' => $url,
            'author' => 'The Pint',
        ];

        if (preg_match('/<meta[^>]+property=["\\\']og:title["\\\'][^>]+content=["\\\']([^"\\\']+)["\\\']/i', $html, $m)) {
            $item['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5);
        } elseif (preg_match('/<title>(.*?)<\/title>/is', $html, $m)) {
            $item['title'] = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_HTML5));
        } else {
            $item['title'] = $url;
        }

        if (preg_match('/<meta[^>]+name=["\\\']description["\\\'][^>]+content=["\\\']([^"\\\']+)["\\\']/i', $html, $m)) {
            $item['content'] = html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5);
        }
        if (preg_match('/<meta[^>]+property=["\\\']article:published_time["\\\'][^>]+content=["\\\']([^"\\\']+)["\\\']/i', $html, $m)) {
            $timestamp = strtotime($m[1]);
            if ($timestamp !== false) {
                $item['timestamp'] = $timestamp;
            }
        }

        if (preg_match('/<meta[^>]+property=["\\\']og:image["\\\'][^>]+content=["\\\']([^"\\\']+)["\\\']/i', $html, $m)) {
            $image = trim($m[1]);
            $item['thumbnail'] = $image;
            $item['enclosures'] = [$image];
        }

        if ($fulltext) {
            $dom = str_get_html($html);
            if ($dom) {
                $article = $dom->find('article', 0);
                if ($article) {
                    $article = defaultLinkTo($article, self::URI);
                    $article = backgroundToImg($article);
                    $article = convertLazyLoading($article);
                    $item['content'] = $article->innertext;
                }
            }
        }

        return array_filter(
            $item,
            static fn($v) => $v !== null && $v !== ''
        );
    }

    public function detectParameters($url)
    {
        if (preg_match('#^https?://(www\\.)?thepint\\.news(?:/archive)?/?$#i', $url)) {
            return [];
        }

        return null;
    }
}