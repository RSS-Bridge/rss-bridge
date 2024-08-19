<?php

class TarnkappeBridge extends FeedExpander
{
    const MAINTAINER = 'Tone866';
    const NAME = 'tarnkappe Bridge';
    const URI = 'https://tarnkappe.info/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns the full articles instead of only the intro';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Category',
            'required' => false,
            'title' => <<<'TITLE'
                If you only want to subscribe to a specific category
                you can enter it here.
                If not, leave it blank to subscribe to everything.
                TITLE,
        ],
        'limit' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => false,
            'title' => 'Specify number of full articles to return',
            'defaultValue' => 10
        ]
    ]];
    const LIMIT = 10;

    public function collectData()
    {
        if (empty($this->getInput('category'))) {
            $category = 'https://tarnkappe.info/feed';
        } else {
            $category = 'https://tarnkappe.info/artikel/' . $this->getInput('category') . '/feed';
        }

        $this->collectExpandableDatas(
            $category,
            $this->getInput('limit') ?: static::LIMIT
        );
    }

    protected function parseItem(array $item)
    {
        if (strpos($item['uri'], 'https://tarnkappe.info/') !== 0) {
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
        $item['content'] = $article->find('a.image-header', 0);

        $article = $article->find('main#article article div.card-content div.content.entry-content', 0);

        // remove unwanted stuff
        foreach (
            $article->find('section, div.menu, p[style]') as $element
        ) {
            $element->remove();
        }

        // reload html, as remove() is buggy
        $article = str_get_html($article->outertext);

        $item['content'] .= $article;

        return $item;
    }
}
