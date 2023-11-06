<?php

class ZeitBridge extends FeedExpander
{
    const MAINTAINER = 'Mynacol';
    const NAME = 'Zeit Online Bridge';
    const URI = 'https://www.zeit.de/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'Startseite'
                => 'https://newsfeed.zeit.de/index',
                'Politik'
                => 'https://newsfeed.zeit.de/politik/index',
                'Wirtschaft'
                => 'https://newsfeed.zeit.de/wirtschaft/index',
                'Gesellschaft'
                => 'https://newsfeed.zeit.de/gesellschaft/index',
                'Kultur'
                => 'https://newsfeed.zeit.de/kultur/index',
                'Wissen'
                => 'https://newsfeed.zeit.de/wissen/index',
                'Digital'
                => 'https://newsfeed.zeit.de/digital/index',
                'ZEIT Campus ONLINE'
                => 'https://newsfeed.zeit.de/campus/index',
                'ZEIT ONLINE Arbeit'
                => 'https://newsfeed.zeit.de/arbeit/index',
                'ZEIT Magazin ONLINE'
                => 'https://newsfeed.zeit.de/zeit-magazin/index',
                'Entdecken'
                => 'https://newsfeed.zeit.de/entdecken/index',
                'Mobilität'
                => 'https://newsfeed.zeit.de/mobilitaet/index',
                'Sport'
                => 'https://newsfeed.zeit.de/sport/index',
                'Alle Inhalte'
                => 'https://newsfeed.zeit.de/all'
            ]
        ],
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => false,
            'title' => 'Specify number of full articles to return',
            'defaultValue' => 5
        ]
    ]];

    public function collectData()
    {
        $url = $this->getInput('category');
        $limit = $this->getInput('limit') ?: 5;

        $this->collectExpandableDatas($url, $limit);
    }

    protected function parseItem(array $item)
    {
        $item['enclosures'] = [];

        $headers = [
            'Cookie: zonconsent=' . date('Y-m-d\TH:i:s.v\Z'),
        ];

        // one-page article
        $article = getSimpleHTMLDOM($item['uri'], $headers);
        if ($article->find('a[href="' . $item['uri'] . '/komplettansicht"]', 0)) {
            $item['uri'] .= '/komplettansicht';
            $article = getSimpleHTMLDOM($item['uri'], $headers);
        }

        $article = defaultLinkTo($article, $item['uri']);
        $item = $this->parseArticle($item, $article);

        return $item;
    }

    private function parseArticle($item, $article)
    {
        $article = $article->find('main', 0);

        // remove known bad elements
        foreach (
            $article->find(
                'aside, .visually-hidden, .carousel-container, #tickaroo-liveblog, .zplus-badge, .article-heading__container--podcast'
            ) as $bad
        ) {
            $bad->remove();
        }
        // reload html, as remove() is buggy
        $article = str_get_html($article->outertext);

        // podcast audio, if available
        $podcast_src = $article->find('.article-heading__podcast audio[src]', 0);
        if ($podcast_src) {
            $item['enclosures'][] = $podcast_src->src;
        }

        // full res images
        foreach ($article->find('img[data-src]') as $img) {
            $img->src = $img->getAttribute('data-src');
            $item['enclosures'][] = $img->src;
        }

        // authors
        $authors = $article->find('*[itemtype*="schema.org/Person"]');
        if (!$authors) {
            $authors = $article->find('.metadata__source');
        }
        if ($authors) {
            $item['author'] = implode(', ', $authors);
        }

        // header image
        $headerimg = $article->find('*[data-ct-row="headerimage"]', 0) ?? $article->find('header', 0);
        if ($headerimg) {
            $item['content'] .= implode('', $headerimg->find('img[src], figcaption'));
        }

        // article content
        $pages = $article->find('.article-page');

        if ($pages) {
            foreach ($pages as $page) {
                $elements = $page->find('p, h2, figcaption, img[src]');
                $item['content'] .= implode('', $elements);
            }
        }

        return $item;
    }
}
