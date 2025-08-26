<?php

class KemonoBridge extends BridgeAbstract
{
    const NAME = 'Kemono';
    const MAINTAINER = 'phantop';
    const URI = 'https://kemono.cr/';
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
        $headers = [
            "Accept: text/css"
        ];

        $api_response = getContents($url . '/profile', $headers);
        $profile = Json::decode($api_response);
        $this->title = ucfirst($profile['name']);

        $url .= '/posts';

        if ($this->getInput('q')) {
            $url .= '?q=' . urlencode($this->getInput('q'));
        }

        // First, get the list of post IDs
        $api_response = getContents($url, $headers);
        $posts_list = Json::decode($api_response);
        
        // Then fetch detailed information for each post
        foreach ($posts_list as $post_summary) {
            $post_id = $post_summary['id'];
            $post_url = $api . $this->getInput('service') . '/user/' . $this->getInput('user') . '/post/' . $post_id;
            
            $post_response = getContents($post_url, $headers);
            $post_data = Json::decode($post_response);
            $post = $post_data['post'];
            
            $item = [];
            $item['author'] = $this->title;
            $item['content'] = $post['content'] ?? '';
            $item['timestamp'] = strtotime($post['published']);
            $item['title'] = $post['title'];
            $item['uid'] = $post['id'];
            $item['uri'] = $this->getURI() . '/post/' . $item['uid'];

            if (isset($post['tags']) && is_array($post['tags']) && !empty($post['tags'])) {
                $item['categories'] = $post['tags'];
            }

            $item['enclosures'] = [];
            if (isset($post['embed']) && is_array($post['embed']) && array_key_exists('url', $post['embed'])) {
                $item['enclosures'][] = $post['embed']['url'];
            }
            if (isset($post['file']) && is_array($post['file']) && array_key_exists('path', $post['file'])) {
                $item['enclosures'][] = parent::getURI() . $post['file']['path'];
            }
            if (isset($post['attachments']) && is_array($post['attachments'])) {
                foreach ($post['attachments'] as $file) {
                    $item['enclosures'][] = parent::getURI() . $file['path'];
                }
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
