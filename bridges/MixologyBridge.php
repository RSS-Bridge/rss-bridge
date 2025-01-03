<?php

class MixologyBridge extends BridgeAbstract
{
    const MAINTAINER = 'swofl';
    const NAME = 'Mixology';
    const URI = 'https://mixology.eu';
    const CACHE_TIMEOUT = 6 * 60 * 60; // 6h
    const DESCRIPTION = 'Get latest blog posts from Mixology';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        $teasers = [];
        $teaserElements = [];

        $teaserElements[] = $html->find('.aufmacher .views-view-responsive-grid__item-inner', 0);
        foreach ($html->find('.block-views-blockmixology-frontpage-block-2 .views-col') as $teaser) {
            $teaserElements[] = $teaser;
        }

        foreach ($teaserElements as $teaser) {
            $teasers[] = $this->parseTeaser($teaser);
        }

        foreach ($teasers as $article) {
            $this->items[] = $this->parseItem($article);
        }
    }

    protected function parseTeaser($teaser)
    {
        $result = [];

        $title = $teaser->find('.views-field-title a', 0);
        $result['title'] = $title->plaintext;
        $result['uri'] = self::URI . $title->href;
        $result['enclosures'] = [];
        $result['enclosures'][] = self::URI . $teaser->find('img', 0)->src;
        $result['uid'] = hash('sha256', $result['title']);

        $categories = $teaser->find('.views-field-field-kategorie', 0);
        if ($categories) {
            $result['categories'] = [];
            foreach ($categories->find('a') as $category) {
                $result['categories'][] = $category->innertext;
            }
        }

        return $result;
    }

    protected function parseItem(array $item)
    {
        $article = getSimpleHTMLDOMCached($item['uri']);

        $authorLink = $article->find('.beitrag-author a', 0);
        if (!empty($authorLink)) {
            $item['author'] = $authorLink->plaintext;
        }

        $timeElement = $article->find('.beitrag-date time', 0);
        if (!empty($timeElement)) {
            $item['timestamp'] = strtotime($timeElement->datetime);
        }

        $content = '';

        $content .= '<img src="' . $item['enclosures'][0] . '"/>';

        foreach ($article->find('article .wpb_content_element>.wpb_wrapper, article .field--type-text-with-summary>.wp-block-columns>.wp-block-column') as $element) {
            $content .= $element->innertext;
        }

        $item['content'] = $content;

        return $item;
    }
}
