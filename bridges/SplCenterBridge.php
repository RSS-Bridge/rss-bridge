<?php

class SplCenterBridge extends FeedExpander
{
    const NAME = 'Southern Poverty Law Center Bridge';
    const URI = 'https://www.splcenter.org';
    const DESCRIPTION = 'Returns the newest posts from the Southern Poverty Law Center';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [[
            'content' => [
                'name' => 'Content',
                'type' => 'list',
                'values' => [
                    'News' => 'news',
                    'Hatewatch' => 'hatewatch',
                ],
                'defaultValue' => 'news',
            ]
        ]
    ];

    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData()
    {
        $url = $this->getURI() . '/rss.xml';
        $this->collectExpandableDatas($url);
    }

    protected function parseItem(array $item)
    {
        $articleHtml = getSimpleHTMLDOMCached($item['uri']);

        foreach ($articleHtml->find('.file') as $index => $media) {
            $articleHtml->find('div.file', $index)->outertext = '<em>' . $media->outertext . '</em>';
        }

        $item['content'] = $articleHtml->find('div#group-content-container', 0)->innertext;
        $item['enclosures'][] = $articleHtml->find('meta[name="twitter:image"]', 0)->content;

        return $item;
    }

    public function getURI()
    {
        if (!is_null($this->getInput('content'))) {
            return self::URI . '/' . $this->getInput('content');
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('content'))) {
            return $this->getKey('content') . ' - Southern Poverty Law Center';
        }

        return parent::getName();
    }
}
