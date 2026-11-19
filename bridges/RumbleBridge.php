<?php

class RumbleBridge extends BridgeAbstract
{
    const NAME = 'Rumble.com';
    const URI = 'https://rumble.com/';
    const DESCRIPTION = 'Fetches the latest channel/user videos and livestreams.';
    const MAINTAINER = 'dvikan, NotsoanoNimus';
    const CACHE_TIMEOUT = 60 * 60; // 1h
    const PARAMETERS = [
        [
            'account' => [
                'name' => 'Account',
                'type' => 'text',
                'required' => true,
                'title' => 'Name of the target account to create into a feed.',
                'defaultValue' => 'bjornandreasbullhansen',
            ],
            'type' => [
                'name' => 'Account Type',
                'type' => 'list',
                'title' => 'The type of profile to create a feed from.',
                'values' => [
                    'Channel (All)' => 'channel',
                    'Channel Videos' => 'channel-videos',
                    'Channel Livestreams' => 'channel-livestream',
                    'User (All)' => 'user',
                ],
            ],
        ]
    ];

    public function collectData()
    {
        $account = $this->getInput('account');
        $type = $this->getInput('type');
        $url = self::getURI();

        if (!preg_match('#^[\w\-_.@]+$#', $account) || strlen($account) > 64) {
            throw new \Exception('Invalid target account.');
        }

        switch ($type) {
            case 'user':
                $url .= "user/$account";
                break;
            case 'channel':
                $url .= "c/$account";
                break;
            case 'channel-videos':
                $url .= "c/$account/videos";
                break;
            case 'channel-livestream':
                $url .= "c/$account/livestreams";
                break;
            default:
                // Shouldn't ever happen.
                throw new \Exception('Invalid media type.');
        }

        $dom = getSimpleHTMLDOM($url);
        $grid = $dom->find('rum-videos-grid script', 0);
        if (! $grid) {
            throwClientException('Failed to find data items in javascript');
        }
        $js = $grid->innertext();
        $data = Json::decode($js);
        foreach ($data['items'] as $item) {
            $title          = $item['title'];
            $url            = $item['url'];
            $image          = $item['thumb'];
            $duration       = $item['duration'];
            $live           = $item['live'];
            $isShort        = $item['is_short'];
            $views          = $item['views'];
            $uploadDate     = $item['upload_date'];

            $publishedAt    = new \DateTimeImmutable($uploadDate);
            $durationMinutes = round($duration / 60);
            $shortText = $isShort ? 'Yes' : 'No';
            $liveText = $live ? 'Yes' : 'No';

            $this->items[] = [
                'title'         => $title,
                'uri'           => $url,
                'timestamp'     => $publishedAt->getTimestamp(),
                'author'        => $account . '@rumble.com',
                'content'       => <<<HTML
                    <a href="$url">
                        <img src="$image">
                    </a> <br><br>

                <b>Duration:</b> $durationMinutes minutes <br>
                <b>Views:</b> $views <br>
                <b>Short:</b> $shortText <br>
                <b>Live:</b> $liveText <br>
                HTML,

                'duration'      => $duration,
                'live'          => $live,
                'is_short'      => $isShort,
                'views'         => $views,
            ];
        }
    }

    public function getName()
    {
        if ($this->getInput('account')) {
            return 'Rumble.com - ' . $this->getInput('account');
        }
        return self::NAME;
    }
}
