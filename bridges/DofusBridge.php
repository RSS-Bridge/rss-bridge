<?php

class DofusBridge extends FeedExpander
{
    const MAINTAINER = 'Jisagi';
    const NAME = 'Dofus';
    const URI = 'https://www.dofus.com/';
    const DESCRIPTION = 'Returns latest news for the MMORPG dofus.';

    const PARAMETERS = [
        [
            'c' => [
                'name' => 'Category',
                'type' => 'list',
                'required' => true,
                'values' => [
                    'News' => 'news',
                    'Changelog' => 'changelog',
                    'Dev Blog' => 'devblog'
                ]
            ],
            'l' => [
                'name' => 'Language',
                'type' => 'list',
                'required' => true,
                'values' => [
                    'English' => 'en',
                    'French' => 'fr',
                    'Spanish' => 'es',
                    'Portuguese' => 'pt'
                ]
            ]
        ]
    ];

    public function getIcon()
    {
        return self::URI . 'favicon.ico';
    }

    public function getURI()
    {
        return self::URI
            . $this->getInput('l')
            . '/rss/'
            . $this->getInput('c')
            . '.xml';
    }

    public function collectData()
    {
        $this->collectExpandableDatas($this->getURI(), 10);
    }
}
