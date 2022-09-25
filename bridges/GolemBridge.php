<?php

class GolemBridge extends FeedExpander
{
    const MAINTAINER = 'Mynacol';
    const NAME = 'Golem Bridge';
    const URI = 'https://www.golem.de/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'Alle News'
                => 'https://rss.golem.de/rss.php?feed=ATOM1.0',
                'Audio/Video'
                => 'https://rss.golem.de/rss.php?ms=audio-video&feed=ATOM1.0',
                'Auto'
                => 'https://rss.golem.de/rss.php?ms=auto&feed=ATOM1.0',
                'Foto'
                => 'https://rss.golem.de/rss.php?ms=foto&feed=ATOM1.0',
                'Games'
                => 'https://rss.golem.de/rss.php?ms=games&feed=ATOM1.0',
                'Handy'
                => 'https://rss.golem.de/rss.php?ms=handy&feed=ATOM1.0',
                'Internet'
                => 'https://rss.golem.de/rss.php?ms=internet&feed=ATOM1.0',
                'Mobil'
                => 'https://rss.golem.de/rss.php?ms=mobil&feed=ATOM1.0',
                'Open Source'
                => 'https://rss.golem.de/rss.php?ms=open-source&feed=ATOM1.0',
                'Politik/Recht'
                => 'https://rss.golem.de/rss.php?ms=politik-recht&feed=ATOM1.0',
                'Security'
                => 'https://rss.golem.de/rss.php?ms=security&feed=ATOM1.0',
                'Desktop-Applikationen'
                => 'https://rss.golem.de/rss.php?ms=desktop-applikationen&feed=ATOM1.0',
                'Software-Entwicklung'
                => 'https://rss.golem.de/rss.php?ms=softwareentwicklung&feed=ATOM1.0',
                'Wirtschaft'
                => 'https://rss.golem.de/rss.php?ms=wirtschaft&feed=ATOM1.0',
                'Wissenschaft'
                => 'https://rss.golem.de/rss.php?ms=wissenschaft&feed=ATOM1.0'
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
    const LIMIT = 5;
    const HEADERS = ['Cookie: golem_consent20=simple|220101;'];

    public function collectData()
    {
        $this->collectExpandableDatas(
            $this->getInput('category'),
            $this->getInput('limit') ?: static::LIMIT
        );
    }

    protected function parseItem($item)
    {
        $item = parent::parseItem($item);
        $item['content'] ??= '';
        $uri = $item['uri'];

        while ($uri) {
            $articlePage = getSimpleHTMLDOMCached($uri, static::CACHE_TIMEOUT, static::HEADERS);

            // URI without RSS feed reference
            $item['uri'] = $articlePage->find('head meta[name="twitter:url"]', 0)->content;

            $author = $articlePage->find('article header .authors .authors__name', 0);
            if ($author) {
                $item['author'] = $author->innertext;
            }

            $item['content'] .= $this->extractContent($articlePage);

            // next page
            $nextUri = $articlePage->find('link[rel="next"]', 0);
            $uri = $nextUri ? static::URI . $nextUri->href : null;
        }

        return $item;
    }

    private function extractContent($page)
    {
        $item = '';

        $article = $page->find('article', 0);

        // delete known bad elements
        foreach (
            $article->find('div[id*="adtile"], #job-market, #seminars,
			div.gbox_affiliate, div.toc, .embedcontent') as $bad
        ) {
            $bad->remove();
        }
        // reload html, as remove() is buggy
        $article = str_get_html($article->outertext);

        if ($pageHeader = $article->find('header.paged-cluster-header h1', 0)) {
            $item .= $pageHeader;
        }

        $header = $article->find('header', 0);
        foreach ($header->find('p, figure') as $element) {
            $item .= $element;
        }

        $content = $article->find('div.formatted', 0);

        // full image quality
        foreach ($content->find('img[data-src-full][src*="."]') as $img) {
            $img->src = $img->getAttribute('data-src-full');
        }

        foreach ($content->find('p, h1, h3, img[src*="."]') as $element) {
            $item .= $element;
        }

        return $item;
    }
}
