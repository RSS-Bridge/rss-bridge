<?php

class HeiseBridge extends FeedExpander
{
    const MAINTAINER = 'Dreckiger-Dan';
    const NAME = 'Heise Online Bridge';
    const URI = 'https://heise.de/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = array(array(
        'category' => array(
            'name' => 'Category',
            'type' => 'list',
            'values' => array(
                'Alle News'
                => 'https://www.heise.de/newsticker/heise-atom.xml',
                'Top-News'
                => 'https://www.heise.de/newsticker/heise-top-atom.xml',
                'Internet-Störungen'
                => 'https://www.heise.de/netze/netzwerk-tools/imonitor-internet-stoerungen/feed/aktuelle-meldungen/',
                'Alle News von heise Developer'
                => 'https://www.heise.de/developer/rss/news-atom.xml'
            )
        ),
        'limit' => array(
            'name' => 'Limit',
            'type' => 'number',
            'required' => false,
            'title' => 'Specify number of full articles to return',
            'defaultValue' => 5
        )
    ));
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
        $authors = $article->find('.a-creator__names', 0)->find('.a-creator__name');
        if ($authors) {
            $item['author'] = implode(', ', array_map(function ($e) {
                return $e->plaintext;
            }, $authors));
        }

        $content = $article->find('div[class*="article-content"]', 0);

        if ($content == null) {
            $content = $article->find('#article_content', 0);
        }

        foreach ($content->find('p, h3, ul, table, pre, img') as $element) {
            $item['content'] .= $element;
        }

        foreach ($content->find('img') as $img) {
            $item['enclosures'][] = $img->src;
        }

        return $item;
    }
}
