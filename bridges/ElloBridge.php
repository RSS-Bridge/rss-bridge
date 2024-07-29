<?php

class ElloBridge extends BridgeAbstract
{
    const MAINTAINER = 'teromene';
    const NAME = 'Ello Bridge';
    const URI = 'https://ello.co/';
    const CACHE_TIMEOUT = 4800; //2hours
    const DESCRIPTION = 'Returns the newest posts for Ello';

    const PARAMETERS = [
        'By User' => [
            'u' => [
                'name' => 'Username',
                'required' => true,
                'exampleValue' => 'zteph',
                'title' => 'Username'
            ]
        ],
        'Search' => [
            's' => [
                'name' => 'Search',
                'required' => true,
                'exampleValue' => 'bird',
                'title' => 'Search'
            ]
        ]
    ];

    public function collectData()
    {
        $header = [
            'Authorization: Bearer ' . $this->getAPIKey()
        ];

        if (!empty($this->getInput('u'))) {
            $postData = getContents(self::URI . 'api/v2/users/~' . urlencode($this->getInput('u')) . '/posts', $header);
        } else {
            $postData = getContents(self::URI . 'api/v2/posts?terms=' . urlencode($this->getInput('s')), $header);
        }

        $postData = json_decode($postData);
        $count = 0;
        foreach ($postData->posts as $post) {
            $item = [];
            $item['author'] = $this->getUsername($post, $postData);
            $item['timestamp'] = strtotime($post->created_at);
            $item['title'] = strip_tags($this->findText($post->summary));
            $item['content'] = $this->getPostContent($post->body);
            $item['enclosures'] = $this->getEnclosures($post, $postData);
            $item['uri'] = self::URI . $item['author'] . '/post/' . $post->token;
            $content = $post->body;

            $this->items[] = $item;
            $count += 1;
        }
    }

    private function findText($path)
    {
        foreach ($path as $summaryElement) {
            if ($summaryElement->kind == 'text') {
                return $summaryElement->data;
            }
        }

        return '';
    }

    private function getPostContent($path)
    {
        $content = '';
        foreach ($path as $summaryElement) {
            if ($summaryElement->kind == 'text') {
                $content .= $summaryElement->data;
            } elseif ($summaryElement->kind == 'image') {
                $alt = '';
                if (property_exists($summaryElement->data, 'alt')) {
                    $alt = $summaryElement->data->alt;
                }
                $content .= '<img src="' . $summaryElement->data->url . '" alt="' . $alt . '" />';
            }
        }

        return $content;
    }

    private function getEnclosures($post, $postData)
    {
        $assets = [];
        foreach ($post->links->assets as $asset) {
            foreach ($postData->linked->assets as $assetLink) {
                if ($asset == $assetLink->id) {
                    $assets[] = $assetLink->attachment->original->url;
                    break;
                }
            }
        }

        return $assets;
    }

    private function getUsername($post, $postData)
    {
        foreach ($postData->linked->users as $user) {
            if ($user->id == $post->links->author->id) {
                return $user->username;
            }
        }
    }

    private function getAPIKey()
    {
        $cacheKey = 'ElloBridge_key';
        $apiKey = $this->cache->get($cacheKey);

        if (!$apiKey) {
            $keyInfo = getContents(self::URI . 'api/webapp-token');
            $apiKey = json_decode($keyInfo)->token->access_token;
            $ttl = 60 * 60 * 20;
            $this->cache->set($cacheKey, $apiKey, $ttl);
        }

        return $apiKey;
    }

    public function getName()
    {
        if (!is_null($this->getInput('u'))) {
            return $this->getInput('u') . ' - Ello Bridge';
        }

        return parent::getName();
    }
}
