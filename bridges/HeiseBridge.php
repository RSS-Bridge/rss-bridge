<?php

class HeiseBridge extends FeedExpander
{
    const MAINTAINER = 'Dreckiger-Dan';
    const NAME = 'Heise Online Bridge';
    const URI = 'https://heise.de/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'Alle News'
                => 'https://www.heise.de/newsticker/heise-atom.xml',
                'Top-News'
                => 'https://www.heise.de/newsticker/heise-top-atom.xml',
                'Internet-Störungen'
                => 'https://www.heise.de/netze/netzwerk-tools/imonitor-internet-stoerungen/feed/aktuelle-meldungen/',
                'Alle News von heise Developer'
                => 'https://www.heise.de/developer/rss/news-atom.xml'
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

    public function collectData()
    {
        $this->collectExpandableDatas(
            $this->getInput('category'),
            $this->getInput('limit') ?: static::LIMIT
        );
    }

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);
        $item['uri'] = explode('?', $item['uri'])[0] . '?seite=all';

        if (strpos($item['uri'], 'https://www.heise.de') !== 0) {
            return $item;
        }

        $article = getSimpleHTMLDOMCached($item['uri']);

        if ($article) {
            $article = defaultLinkTo($article, $item['uri']);
            $item = $this->addArticleToItem($item, $article);
        }

        return $item;
    }

    private function addArticleToItem($item, $article)
    {
        // copy full-res img src to standard img element
        foreach ($article->find('a-img') as $aimg) {
            $img = $aimg->find('img', 0);
            $img->src = $aimg->src;
            // client scales based on aspect ratio in style attribute
            $img->width = '';
            $img->height = '';
        }
        // relink URIs, as the previous a-img tags weren't recognized by this function
        $article = defaultLinkTo($article, $item['uri']);

        // remove unwanted stuff
        foreach ($article->find('figure.branding, a-ad, div.ho-text, noscript img, .opt-in__content-container') as $element) {
            $element->remove();
        }
        // reload html, as remove() is buggy
        $article = str_get_html($article->outertext);

        $header = $article->find('header.a-article-header', 0);
        if ($header) {
            $headerElements = $header->find('p, a-img img, figure img');
            $item['content'] = implode('', $headerElements);

            $authors = $header->find('.a-creator__names .a-creator__name');
            if ($authors) {
                $item['author'] = implode(', ', array_map(function ($e) {
                    return $e->plaintext;
                }, $authors));
            }
        }

        $content = $article->find('.article-content', 0);
        if ($content) {
            $contentElements = $content->find(
                'p, h3, ul, table, pre, a-img img, a-bilderstrecke h2, a-bilderstrecke figure, a-bilderstrecke figcaption'
            );
            $item['content'] .= implode('', $contentElements);
        }
        foreach ($article->find('a-img img, a-bilderstrecke img, figure img') as $img) {
            $item['enclosures'][] = $img->src;
        }

        return $item;
    }
}
