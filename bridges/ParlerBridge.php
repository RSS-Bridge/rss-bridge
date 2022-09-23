<?php

final class ParlerBridge extends BridgeAbstract
{
    const NAME = 'Parler.com bridge';
    const URI = 'https://parler.com';
    const DESCRIPTION = 'Fetches the latest posts from a parler user';
    const MAINTAINER = 'dvikan';
    const CACHE_TIMEOUT = 60 * 15; // 15m
    const PARAMETERS = [
        [
            'user' => [
                'name' => 'User',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'NigelFarage',
            ],
            'limit' => self::LIMIT,
        ]
    ];

    public function collectData()
    {
        $user = trim($this->getInput('user'));
        if (preg_match('#^https?://parler\.com/(\w+)#i', $user, $m)) {
            $user = $m[1];
        }
        $json = getContents(sprintf('https://api.parler.com/v0/public/user/%s/feed/?page=1&limit=20&media_only=0', $user));
        $response = Json::decode($json, false);
        $data = $response->data ?? null;
        if (!$data) {
            throw new \Exception('The returned data is empty');
        }
        foreach ($data as $post) {
            $item = [
                'title'     => $post->body,
                'uri'       => sprintf('https://parler.com/feed/%s', $post->postuuid),
                'author'    => $post->user->username,
                'uid'       => $post->postuuid,
                'content'   => $post->body,
            ];
            $date = $post->date_created;
            $createdAt = date_create($date);
            if ($createdAt) {
                $item['timestamp'] = $createdAt->getTimestamp();
            }
            if (isset($post->image)) {
                $item['content'] .= sprintf('<img loading="lazy" src="%s">', $post->image);
            }
            $this->items[] = $item;
        }
    }
}
