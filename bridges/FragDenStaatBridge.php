<?php

class FragDenStaatBridge extends BridgeAbstract
{
    const MAINTAINER = 'swofl';
    const NAME = 'FragDenStaat';
    const URI = 'https://fragdenstaat.de';
    const CACHE_TIMEOUT = 2 * 60 * 60; // 2h
    const DESCRIPTION = 'Get latest blog posts from FragDenStaat Exklusiv';
    const PARAMETERS = [ [
        'qLimit' => [
            'name' => 'Query Limit',
            'title' => 'Amount of articles to query',
            'type' => 'number',
            'defaultValue' => 5,
        ],
    ] ];

    protected function parseTeaser($teaser)
    {
        $result = [];

        $header = $teaser->find('h3 > a', 0);
        $result['title'] = $header->plaintext;
        $result['uri'] = static::URI . $header->href;
        $result['enclosures'] = [];
        $result['enclosures'][] = $teaser->find('img', 0)->src;
        $result['uid'] = hash('sha256', $result['title']);
        $result['timestamp'] = strtotime($teaser->find('time', 0)->getAttribute('datetime'));

        return $result;
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . '/artikel/exklusiv/');

        $queryLimit = (int) $this->getInput('qLimit');
        if ($queryLimit > 12) {
            $queryLimit = 12;
        }

        $teasers = [];

        $teaserElements = $html->find('article');

        for ($i = 0; $i < $queryLimit; $i++) {
            array_push($teasers, $this->parseTeaser($teaserElements[$i]));
        }

        foreach ($teasers as $article) {
            $articleHtml = getSimpleHTMLDOMCached($article['uri'], static::CACHE_TIMEOUT * 6);
            $articleCore = $articleHtml->find('article.blog-article', 0);

            $content = '';

            $lead = $articleCore->find('div.lead > p', 0)->innertext;

            $content .= '<h2>' . $lead . '</h2>';

            foreach ($articleCore->find('div.blog-content > p, div.blog-content > h3') as $paragraph) {
                $content .= $paragraph->outertext;
            }

            $article['content'] = '<img src="' . $article['enclosures'][0] . '"/>' . $content;

            $article['author'] = '';

            foreach ($articleCore->find('a[rel="author"]') as $author) {
                $article['author'] .= $author->innertext . ', ';
            }

            $article['author'] = rtrim($article['author'], ', ');

            $this->items[] = $article;
        }
    }
}
