<?php

class YandexZenBridge extends BridgeAbstract
{
    const NAME = 'YandexZen Bridge';
    const URI = 'https://dzen.ru';
    const DESCRIPTION = 'Latest posts from the specified channel.';
    const MAINTAINER = 'llamasblade';
    const PARAMETERS = [
        [
            'channelURL' => [
                'name' => 'Channel URL',
                'type' => 'text',
                'required' => true,
                'title' => 'The channel\'s URL',
                'exampleValue' => 'https://dzen.ru/dream_faity_diy',
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
    const _BASE_API_URL_WITH_CHANNEL_NAME = 'https://dzen.ru/api/v3/launcher/more?channel_name=';
    const _BASE_API_URL_WITH_CHANNEL_ID = 'https://dzen.ru/api/v3/launcher/more?channel_id=';

    const _ACCOUNT_URL_WITH_CHANNEL_ID_REGEX = '#^https?://dzen\.ru/id/(?<channelID>[a-z0-9]{24})#';
    const _ACCOUNT_URL_WITH_CHANNEL_NAME_REGEX = '#^https?://dzen\.ru/(?<channelName>[\w\.]+)#';

    private $channelRealName = null;  # as shown in the webpage, not in the URL


    public function collectData()
    {
        $channelURL = $this->getInput('channelURL');

        if (preg_match(self::_ACCOUNT_URL_WITH_CHANNEL_ID_REGEX, $channelURL, $matches)) {
            $channelID = $matches['channelID'];
            $channelAPIURL = self::_BASE_API_URL_WITH_CHANNEL_ID . $channelID;
        } elseif (preg_match(self::_ACCOUNT_URL_WITH_CHANNEL_NAME_REGEX, $channelURL, $matches)) {
            $channelName = $matches['channelName'];
            $channelAPIURL = self::_BASE_API_URL_WITH_CHANNEL_NAME . $channelName;
        } else {
            throwClientException(<<<EOT
Invalid channel URL provided.
The channel\'s URL must be in one of these two forms:
- https://dzen.ru/dream_faity_diy
- https://dzen.ru/id/5ad7777f1aa80ce576015250
EOT);
        }

        $APIResponse = json_decode(getContents($channelAPIURL));

        $this->channelRealName = $APIResponse->header->title;

        $limit = $this->getInput('limit');

        foreach (array_slice($APIResponse->items, 0, $limit) as $post) {
            $item = [];

            $item['uri'] = $post->share_link;
            $item['title'] = $post->title;

            $publicationDateUnixTimestamp = $post->publication_date ?? null;
            if ($publicationDateUnixTimestamp) {
                $item['timestamp'] = date(DateTimeInterface::ATOM, $publicationDateUnixTimestamp);
            }

            $postImage = $post->image ?? null;
            $item['content'] = $post->text;
            if ($postImage) {
                $item['content'] .= "<br /><img src='$postImage' />";
                $item['enclosures'] = [$postImage];
            }

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if (is_null($this->getInput('channelURL'))) {
            return parent::getURI();
        }
        return $this->getInput('channelURL');
    }

    public function getName()
    {
        if (is_null($this->channelRealName)) {
            return parent::getName();
        }
        return $this->channelRealName . '\'s latest zen.yandex posts';
    }
}
