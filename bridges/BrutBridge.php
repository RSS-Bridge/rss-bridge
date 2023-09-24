<?php

class BrutBridge extends BridgeAbstract
{
    const NAME = 'Brut Bridge';
    const URI = 'https://www.brut.media';
    const DESCRIPTION = 'Returns 10 newest videos by category and edition';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [[
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'News' => 'news',
                    'International' => 'international',
                    'Economy' => 'economy',
                    'Science and Technology' => 'science-and-technology',
                    'Entertainment' => 'entertainment',
                    'Sports' => 'sport',
                    'Nature' => 'nature',
                    'Health' => 'health',
                ],
                'defaultValue' => 'news',
            ],
            'edition' => [
                'name' => ' Edition',
                'type' => 'list',
                    'values' => [
                        'United States' => 'us',
                        'United Kingdom' => 'uk',
                        'France' => 'fr',
                        'Spain' => 'es',
                        'India' => 'in',
                        'Mexico' => 'mx',
                ],
                'defaultValue' => 'us',
            ]
        ]
    ];

    public function collectData()
    {
        $url = $this->getURI();
        $html = getSimpleHTMLDOM($url);
        $regex = '/window.__PRELOADED_STATE__ = (.*);/';
        preg_match($regex, $html, $parts);
        $data = Json::decode($parts[1], false);
        foreach ($data->medias->index as $uid => $media) {
            $this->items[] = [
                'uid'       => $uid,
                'title'     => $media->metadata->slug,
                'uri'       => $media->share_url,
                'timestamp' => $media->published_at,
            ];
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('edition')) && !is_null($this->getInput('category'))) {
            return self::URI . '/' . $this->getInput('edition') . '/' . $this->getInput('category');
        }
        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('edition')) && !is_null($this->getInput('category'))) {
            return $this->getKey('category') . ' - ' . $this->getKey('edition') . ' - Brut.';
        }
        return parent::getName();
    }
}
