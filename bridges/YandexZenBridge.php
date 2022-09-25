<?php

class YandexZenBridge extends BridgeAbstract
{
    const NAME = 'YandexZen Bridge';
    const URI = 'https://zen.yandex.com';
    const DESCRIPTION = 'Latest posts from the specified profile.';
    const MAINTAINER = 'llamasblade';
    const PARAMETERS = [
        [
            'username' => [
                'name' => 'Username',
                'type' => 'text',
                'required' => true,
                'title' => 'The account\'s username, found in its URL',
                'exampleValue' => 'dream_faity_diy',
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Number of posts to display. Max is 20.',
                'exampleValue' => '20',
                'defaultValue' => 20,
            ],
        ],
    ];

    # credit: https://github.com/teromene see #1032
    const _API_URL = 'https://zen.yandex.ru/api/v3/launcher/more?channel_name=';

    public function collectData()
    {
        $profile_json = json_decode(getContents($this->getAPIUrl()));
        $limit = $this->getInput('limit');

        foreach (array_slice($profile_json->items, 0, $limit) as $post) {
            $item = [];

            $item['uri'] = $post->share_link;
            $item['title'] = $post->title;
            $item['timestamp'] = date(DateTimeInterface::ATOM, $post->publication_date);
            $item['content'] = $post->text;
            $item['enclosures'] = [
                $post->image,
            ];

            $this->items[] = $item;
        }
    }

    private function getAPIUrl()
    {
        return self::_API_URL . $this->getInput('username');
    }

    public function getURI()
    {
        return self::URI . '/' . $this->getInput('username');
    }

    public function getName()
    {
        if (is_null($this->getInput('username'))) {
            return parent::getName();
        }
        return $this->getInput('username') . '\'s latest zen.yandex posts';
    }
}
