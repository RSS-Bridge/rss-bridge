<?php
class BloombergBridge extends BridgeAbstract
{
    const NAME = 'Bloomberg';
    const URI = 'https://www.bloomberg.com/';
    const DESCRIPTION = 'Trending stories from Bloomberg';
    const MAINTAINER = 'mdemoss';

    const PARAMETERS = array(
    'Trending Stories' => array(),
    'From Search' => array(
    'q' => array(
                'name' => 'Keyword',
                'required' => true
    )
    )
    );

    public function getName()
    {
        switch($this->queriedContext) {
        case 'Trending Stories':
            return self::NAME . ' Trending Stories';
        case 'From Search':
            if (!is_null($this->getInput('q'))) {
                    return self::NAME . ' Search : ' . $this->getInput('q');
            }
            break;
        }

        return parent::getName();
    }

    public function collectData()
    {
        switch($this->queriedContext) {
        case 'Trending Stories': // Get list of top new <article>s from the front page.
            $html = getSimpleHTMLDOMCached($this->getURI(), 300);
            $stories = $html->find('ul.top-news-v3__stories article.top-news-v3-story');
            break;
        case 'From Search': // Get list of <article> elements from search.
            $html = getSimpleHTMLDOMCached($this->getURI() . 'search?sort=time:desc&page=1&query=' . urlencode($this->getInput('q')), 300);
            $stories = $html->find('div.search-result-items article.search-result-story');
            break;
        }
        foreach ($stories as $element) {
            $item['uri'] = $element->find('h1 a', 0)->href;
            if (preg_match('#^https://#i', $item['uri']) !== 1) {
                $item['uri'] = $this->getURI() . $item['uri'];
            }
            $articleHtml = getSimpleHTMLDOMCached($item['uri']);
            if (!$articleHtml) {
                continue;
            }
            $item['title'] = $element->find('h1 a', 0)->plaintext;
            $item['timestamp'] = strtotime($articleHtml->find('meta[name=iso-8601-publish-date],meta[name=date]', 0)->content);
            $item['content'] = $articleHtml->find('meta[name=description]', 0)->content;
            $this->items[] = $item;
        }
    }
}
