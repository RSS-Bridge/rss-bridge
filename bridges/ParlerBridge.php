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

        $posts = $this->fetchParlerProfileFeed($user);

        foreach ($posts as $post) {
            // For some reason, the post data is placed inside primary attribute
            $primary = $post->primary;

            $item = [
                'title'     => mb_substr($primary->body, 0, 100),
                'uri'       => sprintf('https://parler.com/feed/%s', $primary->uuid),
                'author'    => $primary->username,
                'uid'       => $primary->uuid,
                'content'   => nl2br($primary->full_body),
            ];

            $date = DateTimeImmutable::createFromFormat('m/d/YH:i A', $primary->date_str . $primary->time_str);
            if ($date) {
                $item['timestamp'] = $date->getTimestamp();
            } else {
                Debug::log(sprintf('Unable to parse data from Parler.com: "%s"', $date));
            }

            if (isset($primary->image)) {
                $item['enclosures'][] = $primary->image;
                $item['content'] .= sprintf('<img loading="lazy" src="%s">', $primary->image);
            }

            $this->items[] = $item;
        }
    }

    private function fetchParlerProfileFeed(string $user): array
    {
        $json = getContents('https://parler.com/open-api/ProfileFeedEndpoint.php', [], [
            CURLOPT_POSTFIELDS => http_build_query([
                'user' => $user,
                'page' => '1',
            ]),
        ]);
        $response = json_decode($json);
        if ($response === false) {
            throw new \Exception('Unable to decode json from Parler');
        }
        if ($response->status !== 'ok') {
            throw new \Exception('Did not get OK from Parler');
        }
        if ($response->data === []) {
            throw new \Exception('Unknown Parler username');
        }
        return $response->data;
    }
}
