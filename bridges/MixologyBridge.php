<?php

class MixologyBridge extends FeedExpander
{
    const MAINTAINER = 'swofl';
    const NAME = 'Mixology';
    const URI = 'https://mixology.eu';
    const CACHE_TIMEOUT = 6 * 60 * 60; // 6h
    const DESCRIPTION = 'Get latest blog posts from Mixology';
    const PARAMETERS = [ [
        'limit' => self::LIMIT,
    ] ];

    public function collectData()
    {
        $feed_url = self::URI . '/feed';
        $limit = $this->getInput('limit') ?? 10;
        $this->collectExpandableDatas($feed_url, $limit);
    }

    protected function parseItem(array $item)
    {
        $article = getSimpleHTMLDOMCached($item['uri']);

        $content = '';

        $headerImage = $article->find('div.edgtf-full-width img.wp-post-image', 0);

        if (is_object($headerImage)) {
            $item['enclosures'] = [];
            $item['enclosures'][] = $headerImage->src;
            $content .= '<img src="' . $headerImage->src . '"/>';
        }

        foreach ($article->find('article .wpb_content_element > .wpb_wrapper') as $element) {
            $content .= $element->innertext;
        }

        $item['content'] = $content;

        $item['categories'] = [];

        foreach ($article->find('.edgtf-tags > a') as $tag) {
            $item['categories'][] = $tag->plaintext;
        }

        return $item;
    }
}
