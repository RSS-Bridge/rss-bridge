<?php

class CNETFranceBridge extends FeedExpander
{
    const MAINTAINER = 'leomaradan';
    const NAME = 'CNET France';
    const URI = 'https://www.cnetfrance.fr/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'CNET France RSS with filters';
    const PARAMETERS = [
        'filters' => [
            'title' => [
                'name' => 'Exclude by title',
                'required' => false,
                'title' => 'Title term, separated by semicolon (;)',
                'exampleValue' => 'bon plan;bons plans;au meilleur prix;des meilleures offres;Amazon Prime Day;RED by SFR ou B&You'
            ],
            'url' => [
                'name' => 'Exclude by url',
                'required' => false,
                'title' => 'URL term, separated by semicolon (;)',
                'exampleValue' => 'bon-plan;bons-plans'
            ]
        ]
    ];

    private $bannedTitle = [];
    private $bannedURL = [];

    public function collectData()
    {
        $title = $this->getInput('title');
        $url = $this->getInput('url');

        if ($title !== null) {
            $this->bannedTitle = explode(';', $title);
        }

        if ($url !== null) {
            $this->bannedURL = explode(';', $url);
        }

        $this->collectExpandableDatas('https://www.cnetfrance.fr/feeds/rss/news/');
    }

    protected function parseItem($feedItem)
    {
        $item = parent::parseItem($feedItem);

        foreach ($this->bannedTitle as $term) {
            if (preg_match('/' . $term . '/mi', $item['title']) === 1) {
                return null;
            }
        }

        foreach ($this->bannedURL as $term) {
            if (preg_match('/' . $term . '/mi', $item['uri']) === 1) {
                return null;
            }
        }

        return $item;
    }
}
