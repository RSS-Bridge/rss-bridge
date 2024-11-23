<?php

class MixologyBridge extends BridgeAbstract
{
    const MAINTAINER = 'swofl';
    const NAME = 'Mixology';
    const URI = 'https://mixology.eu';
    const CACHE_TIMEOUT = 2 * 60 * 60; // 2h
    const DESCRIPTION = 'Get latest blog posts from Mixology';
    const PARAMETERS = [ [
        'qLimit' => [
            'name' => 'Query Limit',
            'title' => 'Amount of articles to query',
            'type' => 'number',
            'defaultValue' => 8,
        ],
    ] ];

    protected function parseTeaser($teaser)
    {
        $result = [];

        $header = $teaser->find('h3 > a', 0);
        $result['title'] = $header->plaintext;
        $result['uri'] = $header->href;
        $result['enclosures'] = [];
        $result['enclosures'][] = $teaser->find('img', 0)->src;
        $result['uid'] = $teaser->id;
        $result['categories'] = [];

        foreach($teaser->find('.edgtf-post-info-category > a') as $tag) {
            $result['categories'][] = $tag->plaintext;
        }

        return $result;
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . '?lang=de');

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
            $content = $articleHtml->find('article[id=article]', 0);

            $content = '';

            foreach ($articleHtml->find('.wpb_content_element > .wpb_wrapper') as $element) {
                $content .= $element->innertext;
            }

            $article['content'] = '<img src="' . $article['enclosures'][0] . '"/>' . $content;
            $article['author'] = $articleHtml->find('.edgtf-post-info-author-link', 0)->innertext;
            $article['timestamp'] = strtotime($articleHtml->find('.edgtf-post-info-date > a', 0)->innertext);

            $this->items[] = $article;
        }
    }
}
