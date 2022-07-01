<?php

class WorldCosplayBridge extends BridgeAbstract
{
    const NAME = 'WorldCosplay Bridge';
    const URI = 'https://worldcosplay.net/';
    const DESCRIPTION = 'Returns WorldCosplay photos';
    const MAINTAINER = 'AxorPL';

    const API_CHARACTER = 'api/photo/list.json?character_id=%u&limit=%u';
    const API_COSPLAYER = 'api/member/photos.json?member_id=%u&limit=%u';
    const API_SERIES = 'api/photo/list.json?title_id=%u&limit=%u';
    const API_TAG = 'api/tag/photo_list.json?id=%u&limit=%u';

    const CONTENT_HTML
        = '<a href="%s" target="_blank"><img src="%s" alt="%s" title="%s"></a>';

    const ERR_CONTEXT = 'No context provided';
    const ERR_QUERY = 'Unable to query: %s';

    const LIMIT_MIN = 1;
    const LIMIT_MAX = 24;

    const PARAMETERS = [
        'Character' => [
            'cid' => [
                'name' => 'Character ID',
                'type' => 'number',
                'required' => true,
                'title' => 'WorldCosplay character ID',
                'exampleValue' => 18204
            ]
        ],
        'Cosplayer' => [
            'uid' => [
                'name' => 'Cosplayer ID',
                'type' => 'number',
                'required' => true,
                'title' => 'Cosplayer\'s WorldCosplay profile ID',
                'exampleValue' => 406782
            ]
        ],
        'Series' => [
            'sid' => [
                'name' => 'Series ID',
                'type' => 'number',
                'required' => true,
                'title' => 'WorldCosplay series ID',
                'exampleValue' => 3139
            ]
        ],
        'Tag' => [
            'tid' => [
                'name' => 'Tag ID',
                'type' => 'number',
                'required' => true,
                'title' => 'WorldCosplay tag ID',
                'exampleValue' => 33643
            ]
        ],
        'global' => [
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Maximum number of photos to return',
                'exampleValue' => 5,
                'defaultValue' => 5
            ]
        ]
    ];

    public function collectData()
    {
        $limit = $this->getInput('limit');
        $limit = min(self::LIMIT_MAX, max(self::LIMIT_MIN, $limit));
        switch ($this->queriedContext) {
            case 'Character':
                $id = $this->getInput('cid');
                $url = self::API_CHARACTER;
                break;
            case 'Cosplayer':
                $id = $this->getInput('uid');
                $url = self::API_COSPLAYER;
                break;
            case 'Series':
                $id = $this->getInput('sid');
                $url = self::API_SERIES;
                break;
            case 'Tag':
                $id = $this->getInput('tid');
                $url = self::API_TAG;
                break;
            default:
                returnClientError(self::ERR_CONTEXT);
        }
        $url = self::URI . sprintf($url, $id, $limit);

        $json = json_decode(getContents($url));
        if ($json->has_error) {
            returnServerError($json->message);
        }
        $list = $json->list;

        foreach ($list as $img) {
            $image = $img->photo ?? $img;
            $item = [
                'uri' => self::URI . substr($image->url, 1),
                'title' => $image->subject,
                'timestamp' => $image->created_at,
                'author' => $img->member->global_name,
                'enclosures' => [$image->large_url],
                'uid' => $image->id,
            ];
            $item['content'] = sprintf(
                self::CONTENT_HTML,
                $item['uri'],
                $item['enclosures'][0],
                $item['title'],
                $item['title']
            );
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Character':
                $id = $this->getInput('cid');
                break;
            case 'Cosplayer':
                $id = $this->getInput('uid');
                break;
            case 'Series':
                $id = $this->getInput('sid');
                break;
            case 'Tag':
                $id = $this->getInput('tid');
                break;
            default:
                return parent::getName();
        }
        return sprintf('%s %u - ', $this->queriedContext, $id) . self::NAME;
    }
}
