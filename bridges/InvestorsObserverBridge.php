<?php

declare(strict_types=1);

class InvestorsObserverBridge extends BridgeAbstract
{
    const NAME          = 'InvestorsObserver';
    const URI           = 'https://investorsobserver.com';
    const DESCRIPTION   = 'Fetches the latest stock news';
    const MAINTAINER    = 'tillcash';
    const CACHE_TIMEOUT = 3600; // 1 hour
    const MAX_ARTICLES  = 5;

    public function collectData()
    {
        $urls = get_sitemap(self::URI . '/news-sitemap.xml');

        foreach ($urls as $entry) {
            $title = null;
            $pubDate = null;

            $url     = trim((string) $entry['loc']);
            $lastmod = trim((string) $entry['lastmod']);

            if (!$url) {
                continue;
            }

            if (isset($entry['news'])) {
                $news = $entry['news'];

                if ($news) {
                    $title = trim((string) $news['title']);
                    $pubDate = trim((string) $news['publication_date']);
                }
            }

            if (!$title) {
                continue;
            }

            $timestamp = $pubDate ? strtotime($pubDate) : ($lastmod ? strtotime($lastmod) : '');

            $this->items[] = [
                'title'     => $title,
                'uri'       => $url,
                'uid'       => $url,
                'timestamp' => $timestamp,
                'content'   => $this->fetchFullArticle($url),
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

         // Remove unnecessary elements
        $removeSelectors = [
            'script',
            'style',
            'div.links-bar',
            'div.a-wrapper',
            'div.related-articles',
            'hr.space_media-size',
        ];

        foreach ($removeSelectors as $selector) {
            foreach ($article->find($selector) as $element) {
                $element->outertext = '';
            }
        }

        return $article->innertext;
    }
}
