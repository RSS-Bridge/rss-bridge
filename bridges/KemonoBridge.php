<?php

class KemonoBridge extends BridgeAbstract
{
    const NAME = 'Kemono';
    const MAINTAINER = 'phantop';
    const URI = 'https://kemono.su/';
    const DESCRIPTION = 'Returns posts from Kemono.';
    const PARAMETERS = [[
        'service' => [
            'name' => 'Content service',
            'type' => 'list',
            'defaultValue' => 'patreon',
            'values' => [
                'Patreon' => 'patreon',
                'Pixiv Fanbox' => 'fanbox',
                'Fantia' => 'fantia',
                'Boosty' => 'boosty',
                'Gumroad' => 'gumroad',
                'SubscribeStar' => 'subscribestar',
            ]
        ],
        'user' => [
            'name' => 'User ID/Name',
            'exampleValue' => '9069743', # Thomas Joy
            'required' => true,
        ],
        'q' => [
            'name' => 'Search query',
            'exampleValue' => 'classic',
            'required' => false,
        ]
    ]];

    private $title;

    public function collectData()
    {
        $api = parent::getURI() . 'api/v1/';
        $url = $api . $this->getInput('service') . '/user/' . $this->getInput('user');

        $api_response = getContents($url . '/profile');
        $profile = Json::decode($api_response);
        $this->title = ucfirst($profile['name']);

        if ($this->getInput('q')) {
            $url .= '?q=' . urlencode($this->getInput('q'));
        }
        $api_response = getContents($url);
        $json = Json::decode($api_response);


        foreach ($json as $element) {
            $item = [];
            $item['author'] = $this->title;
            $item['content'] = $element['content'];
            $item['timestamp'] = strtotime($element['published']);
            $item['title'] = $element['title'];
            $item['uid'] = $element['id'];
            $item['uri'] = $this->getURI() . '/post/' . $item['uid'];

            if ($element['tags']) {
                $tags = $element['tags'];
                if (is_array($tags)) {
                    $item['categories'] = $tags;
                } else {
                    $tags = preg_replace('/^{/', '', $tags);
                    $tags = preg_replace('/}$/', '', $tags);
                    $tags = preg_replace('/"/', '', $tags);
                    $item['categories'] = explode(',', $tags);
                }
            }

            $item['enclosures'] = [];
            if (array_key_exists('url', $element['embed'])) {
                $item['enclosures'][] = $element['embed']['url'];
            }
            if (array_key_exists('path', $element['file'])) {
                $element['attachments'][] = $element['file'];
            }
            foreach ($element['attachments'] as $file) {
                $item['enclosures'][] = parent::getURI() . $file['path'];
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        $name = parent::getName();
        if (isset($this->title)) {
            $name .= ' - ' . $this->title;
        }
        return $name;
    }

    public function getURI()
    {
        $uri = parent::getURI() . $this->getInput('service') . '/user/' . $this->getInput('user');
        return $uri;
    }
}
