<?php

class SplCenterBridge extends FeedExpander
{
    const NAME = 'Southern Poverty Law Center Bridge';
    const URI = 'https://www.splcenter.org';
    const DESCRIPTION = 'Returns the newest posts from the Southern Poverty Law Center';
    const MAINTAINER = 'tyler000000';
    const PARAMETERS = [[
            'content' => [
                'name' => 'Content',
                'type' => 'list',
                'values' => [
                    'Stories' => 'stories',
                    'Hatewatch' => 'hatewatch',
                ],
                'defaultValue' => 'stories',
            ]
        ]
    ];

    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData()
    {
        $dom = getSimpleHTMLDOM($this->getURI());
        foreach ($dom->find('ul.wp-block-post-template li') as $li) {
            $a = $li->find('h3 > a', 0);
            if ($a && trim($a->plaintext) !== '') {
                $this->items[] = [
                    'title' => $a->plaintext,
                    'uri' => $a->href,
                    'author' => $li->find('p.wp-block-splc-authors__name', 0)->plaintext,
                    'content' => $li->find('p.wp-block-post-excerpt__excerpt', 0)->plaintext,
                    'timestamp' => date("U",strtotime($li->find('time', 0)->getAttribute('datetime'))),
                ];
            }
        }
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
            return self::URI . '/resources/' . $this->getInput('content');
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
