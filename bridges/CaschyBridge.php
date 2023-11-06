<?php

class CaschyBridge extends FeedExpander
{
    const MAINTAINER = 'Tone866';
    const NAME = 'Caschys Blog Bridge';
    const URI = 'https://stadt-bremerhaven.de/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'type' => 'list',
            'values' => [
                'Alle News'
                => 'https://stadt-bremerhaven.de/feed/'
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

    protected function parseItem(array $item)
    {
        if (strpos($item['uri'], 'https://stadt-bremerhaven.de/') !== 0) {
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
        // remove unwanted stuff
        foreach (
            $article->find('div.video-container, div.aawp, p.aawp-disclaimer, iframe.wp-embedded-content, 
            div.wp-embed, p.wp-caption-text, script') as $element
        ) {
            $element->remove();
        }
        // reload html, as remove() is buggy
        $article = str_get_html($article->outertext);

        $categories = $article->find('div.post-category a');
        foreach ($categories as $category) {
            $item['categories'][] = $category->plaintext;
        }

        $content = $article->find('div.entry-inner', 0);
        $item['content'] = $content;

        return $item;
    }
}
