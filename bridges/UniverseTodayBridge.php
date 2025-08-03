<?php

class UniverseTodayBridge extends FeedExpander
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Universe Today Bridge';
    const URI = 'https://www.universetoday.com/';
    const DESCRIPTION = 'Returns the latest articles from Universe Today.';

    const PARAMETERS = [
        '' => [
            'limit' => [
                'name' => 'Feed Item Limit',
                'required' => true,
                'type' => 'number',
                'defaultValue' => 10,
                'title' => 'Maximum number of returned feed items. Default 10'
            ],
        ],
    ];

    public function collectData()
    {
        $this->collectExpandableDatas(self::URI . 'feed', (int)$this->getInput('limit'));
    }

    protected function parseItem(array $item)
    {
        $dom = getSimpleHTMLDOMCached($item['uri'], 7 * 24 * 60 * 60);
        $article_main = $dom->find('main > article', 0);

        // Mostly YouTube videos
        $iframes = $article_main->find('iframe');
        foreach ($iframes as $iframe) {
            $iframe->outertext = '<a href="' . $iframe->src . '">' . $iframe->src . '</a>';
        }
        $article_main = defaultLinkTo($article_main, self::URI);

        $author_bio = $article_main->find('div.author-bio', 0);
        if ($author_bio) {
            $author_bio->parent->removeChild($author_bio);
        }
        $article_nav = $article_main->find('nav.article-navigation', 0);
        if ($article_nav) {
            $article_nav->parent->removeChild($article_nav);
        }

        $item['content'] = $article_main->innertext;

        return $item;
    }
}
