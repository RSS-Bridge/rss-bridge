<?php

class CNETBridge extends SitemapBridge
{
    const MAINTAINER = 'ORelio';
    const NAME = 'CNET News';
    const URI = 'https://www.cnet.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns the newest articles.';
    const PARAMETERS = [
        [
            'topic' => [
                'name' => 'Topic',
                'type' => 'list',
                'values' => [
                    'All articles' => '',
                    'Tech' => 'tech',
                    'Money' => 'personal-finance',
                    'Home' => 'home',
                    'Wellness' => 'health',
                    'Energy' => 'home/energy-and-utilities',
                    'Deals' => 'deals',
                    'Computing' => 'tech/computing',
                    'Mobile' => 'tech/mobile',
                    'Science' => 'science',
                    'Services' => 'tech/services-and-software'
                ]
            ],
            'limit' => self::LIMIT
        ]
    ];

    public function collectData()
    {
        $topic = $this->getInput('topic');
        $limit = $this->getInput('limit');
        $limit = empty($limit) ? 10 : $limit;

        $url_pattern = empty($topic) ? '' : self::URI . $topic;
        $sitemap_latest = self::URI . 'sitemaps/article/' . date('Y/m') . '.xml';
        $sitemap_previous = self::URI . 'sitemaps/article/' . date('Y/m', strtotime('last day of previous month')) . '.xml';

        $links = array_merge(
            $this->sitemapXmlToList($this->getSitemapXml($sitemap_latest, true), $url_pattern, $limit),
            $this->sitemapXmlToList($this->getSitemapXml($sitemap_previous, true), $url_pattern, $limit)
        );

        if ($limit > 0 && count($links) > $limit) {
            $links = array_slice($links, 0, $limit);
        }

        if (empty($links)) {
            returnClientError('Failed to retrieve article list');
        }

        foreach ($links as $article_uri) {
            $article_dom = convertLazyLoading(getSimpleHTMLDOMCached($article_uri));
            $title = trim($article_dom->find('h1', 0)->plaintext);
            $author = $article_dom->find('span.c-assetAuthor_name', 0);
            $headline = $article_dom->find('p.c-contentHeader_description', 0);
            $content = $article_dom->find('div.c-pageArticle_content, div.single-article__content, div.article-main-body', 0);
            $date = null;
            $enclosure = null;

            foreach ($article_dom->find('script[type=application/ld+json]') as $ldjson) {
                $datePublished = extractFromDelimiters($ldjson->innertext, '"datePublished":"', '"');
                if ($datePublished !== false) {
                    $date = strtotime($datePublished);
                }
                $imageObject = extractFromDelimiters($ldjson->innertext, 'ImageObject","url":"', '"');
                if ($imageObject !== false) {
                    $enclosure = $imageObject;
                }
            }

            foreach ($content->find('div.c-shortcodeGallery') as $cleanup) {
                $cleanup->outertext = '';
            }

            foreach ($content->find('figure') as $figure) {
                $img = $figure->find('img', 0);
                if ($img) {
                    $figure->outertext = $img->outertext;
                }
            }

            $content = $content->innertext;

            if ($enclosure) {
                $content = "<div><img src=\"$enclosure\" /></div>" . $content;
            }

            if ($headline) {
                $content = '<p><b>' . $headline->plaintext . '</b></p><br />' . $content;
            }

            $item = [];
            $item['uri'] = $article_uri;
            $item['title'] = $title;

            if ($author) {
                $item['author'] = $author->plaintext;
            }

            $item['content'] = $content;

            if (!is_null($date)) {
                $item['timestamp'] = $date;
            }

            if (!is_null($enclosure)) {
                $item['enclosures'] = [$enclosure];
            }

            $this->items[] = $item;
        }
    }
}
