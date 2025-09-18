<?php

class KemonoBridge extends BridgeAbstract
{
    const NAME = 'Kemono';
    const MAINTAINER = 'phantop';
    const URI = 'https://kemono.cr/';
    const DESCRIPTION = 'Returns posts from Kemono and Coomer.';
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
                'DLSite' => 'dlsite',

                'OnlyFans' => 'onlyfans',
                'Fansly' => 'fansly',
                'CandFans' => 'candfans',
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
        ],
        'limit' => self::LIMIT,
    ]];

    private $author;

    private function isCoomer()
    {
        $haystack = $this->getInput('service') ?? '';
        return str_contains($haystack, 'fans');
    }

    private function baseURI()
    {
        if ($this->isCoomer()) {
            return 'https://coomer.st/';
        }
        return parent::getURI();
    }

    private function getJson(string $endpoint)
    {
        $api = $this->baseURI() . 'api/v1/' . $this->getInput('service');
        $url = $api . $this->getInput('service') . '/user/' . $this->getInput('user');
        $header = [ 'Accept: text/css' ]; // Required by API

        $api_response = getContents("$api$endpoint", $header);
        return Json::decode($api_response);
    }

    public function collectData()
    {
        $user = '/user/' . $this->getInput('user');
        $profile = $this->getJson("$user/profile");
        $this->author = ucfirst($profile['name']);

        $json = $this->getJson("$user/posts?q=" . urlencode($this->getInput('q')));
        $elements = array_slice($json, 0, $this->getInput('limit'));

        foreach ($elements as $element) {
            $element = $this->getJson($user . '/post/' . $element['id']);
            $post = $element['post'];

            $item = [
                'author' => $this->author,
                'categories' => $post['tags'],
                'content' => $post['content'],
                'timestamp' => strtotime($post['published']),
                'title' => $post['title'],
                'uid' => $post['id'],
                'uri' => $this->getURI() . '/post/' . $post['id'],
            ];

            $item['enclosures'] = [];
            if (array_key_exists('url', $post['embed'])) {
                $item['enclosures'][] = $post['embed']['url'];
            }
            if (array_key_exists('path', $post['file'])) {
                $element['attachments'][] = $post['file'];
            }
            foreach ($element['attachments'] as $file) {
                $item['enclosures'][] = $this->baseURI() . $file['path'];
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        $name = parent::getName();
        if (isset($this->author)) {
            $name .= ' - ' . $this->author;
        }
        return $name;
    }

    public function getURI()
    {
        $service = $this->getInput('service');
        $user = $this->getInput('user');

        $uri = $this->baseURI() . $service . '/user/' . $user;
        return $uri;
    }
}
