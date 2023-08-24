<?php

class PinterestBridge extends FeedExpander
{
    const MAINTAINER = 'pauder';
    const NAME = 'Pinterest Bridge';
    const URI = 'https://www.pinterest.com';
    const DESCRIPTION = 'Returns the newest images on a board';

    const PARAMETERS = [
        'By username and board' => [
            'u' => [
                'name' => 'username',
                'exampleValue' => 'VIGOIndustries',
                'required' => true
            ],
            'b' => [
                'name' => 'board',
                'exampleValue' => 'bathroom-remodels',
                'required' => true
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://s.pinimg.com/webapp/style/images/favicon-9f8f9adf.png';
    }

    public function collectData()
    {
        $this->collectExpandableDatas($this->getURI() . '.rss');
        $this->fixLowRes();
    }

    private function fixLowRes()
    {
        $newitems = [];
        $pattern = '/https\:\/\/i\.pinimg\.com\/[a-zA-Z0-9]*x\//';
        foreach ($this->items as $item) {
            $item['content'] = preg_replace($pattern, 'https://i.pinimg.com/originals/', $item['content']);
            $newitems[] = $item;
        }
        $this->items = $newitems;
    }

    public function getURI()
    {
        if ($this->queriedContext === 'By username and board') {
            return self::URI . '/' . urlencode($this->getInput('u')) . '/' . urlencode($this->getInput('b'));
        }

        return parent::getURI();
    }

    public function getName()
    {
        if ($this->queriedContext === 'By username and board') {
            return $this->getInput('u') . ' - ' . $this->getInput('b') . ' - ' . self::NAME;
        }

        return parent::getName();
    }
}
