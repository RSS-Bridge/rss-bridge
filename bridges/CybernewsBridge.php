<?php

declare(strict_types=1);

class CybernewsBridge extends BridgeAbstract
{
    const NAME          = 'Cybernews';
    const URI           = 'https://cybernews.com';
    const DESCRIPTION   = 'Fetches the latest news from Cybernews';
    const MAINTAINER    = 'tillcash';
    const CACHE_TIMEOUT = 3600; // 1 hour
    const MAX_ARTICLES  = 5;

    public function collectData()
    {
        $urls = get_sitemap('https://cybernews.com/news-sitemap.xml');

        foreach ($urls as $entry) {
            $url     = $entry['loc'];
            $lastmod = $entry['lastmod'];

            if (!$url) {
                continue;
            }

            $pathParts = explode('/', trim(parse_url($url, PHP_URL_PATH), '/'));
            $category  = isset($pathParts[0]) && $pathParts[0] !== '' ? $pathParts[0] : '';

            // Skip non-English versions
            // if (in_array($category, ['nl', 'de', 'es', 'it'], true)) {
            //     continue;
            // }

            $title      = '';

            if (isset($entry['news'])) {
                $news = $entry['news'];
                if ($news) {
                    $title = trim((string) $news['title']);
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

            if (count($this->items) >= self::MAX_ARTICLES) {
                break;
            }
        }
    }

    private function fetchFullArticle(string $url): string
    {
        $html = getSimpleHTMLDOMCached($url);
        if (!$html) {
            return 'Unable to fetch article content';
        }

        $article = $html->find('article', 0);
        if (!$article) {
            return 'Unable to parse article content';
        }

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
