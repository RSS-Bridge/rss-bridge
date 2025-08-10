<?php

class CybernewsBridge extends BridgeAbstract
{
    const NAME = 'Cybernews';
    const URI = 'https://cybernews.com';
    const DESCRIPTION = 'Fetches the latest news from Cybernews';
    const MAINTAINER = 'tillcash';
    const CACHE_TIMEOUT = 3600; // 1 hour
    const MAX_ARTICLES = 5;

    public function collectData()
    {
        $header = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36'
        ];

        $sitemapXml = getContents($url, $header);
        if (!$sitemapXml) {
            throwServerException('Could not retrieve Cybernews sitemap');
        }

        $sitemap = simplexml_load_string($sitemapXml, null, LIBXML_NOCDATA);
        if (!$sitemap) {
            throwServerException('Failed to parse Cybernews sitemap');
        }

        $articleCount = 0;

        foreach ($sitemap->url as $entry) {
            $url     = trim((string) $entry->loc);
            $lastmod = trim((string) $entry->lastmod);

            if (!$url) {
                continue;
            }

            $pathParts = explode('/', trim(parse_url($url, PHP_URL_PATH), '/'));
            $category  = isset($pathParts[0]) && $pathParts[0] !== '' ? $pathParts[0] : '';

            // Skip non-English versions of articles?
            if (in_array($category, ['nl', 'de'])) {
                continue;
            }

            $namespaces = $entry->getNamespaces(true);
            $title      = '';
            if (isset($namespaces['news'])) {
                $news = $entry->children($namespaces['news'])->news;
                if ($news) {
                    $title = trim((string) $news->title);
                }
            }

            if (!$title) {
                continue;
            }

            $this->items[] = [
                'title'      => $title,
                'uri'        => $url,
                'uid'        => $url,
                'timestamp'  => strtotime($lastmod),
                'categories' => $category ? [$category] : [],
                'content'    => $this->fetchFullArticle($url),
            ];

            if (++$articleCount >= self::MAX_ARTICLES) {
                break;
            }
        }
    }

    private function fetchFullArticle($url)
    {
        $html = getSimpleHTMLDOMCached($url);
        if (!$html) {
            return 'Failed to fetch article content';
        }

        $article = $html->find('article', 0);
        if (!$article) {
            return 'Failed to parse article content';
        }

        // Remove unnecessary elements
        $removeSelectors = [
            'script',
            'style',
            'div.links-bar',
            'div.google-news-cta',
            'div.a-wrapper',
            'div.embed_youtube',
        ];
        foreach ($removeSelectors as $selector) {
            foreach ($article->find($selector) as $element) {
                $element->outertext = '';
            }
        }

        // Handle lazy-loaded images
        foreach ($article->find('img') as $img) {
            if (!empty($img->{'data-src'})) {
                $img->src = $img->{'data-src'};
                unset($img->{'data-src'});
            }
        }

        return $article->innertext;
    }
}
