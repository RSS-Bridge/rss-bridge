<?php

class RoosterTeethBridge extends BridgeAbstract
{
    const MAINTAINER = 'tgkenney';
    const NAME = 'Rooster Teeth';
    const URI = 'https://roosterteeth.com';
    const DESCRIPTION = 'Gets the latest channel videos from the Rooster Teeth website';
    const API = 'https://svod-be.roosterteeth.com/';

    const PARAMETERS = [
        'Options' => [
            'channel' => [
                'type' => 'list',
                'name' => 'Channel',
                'title' => 'Select a channel to filter by',
                'values' => [
                    'All channels' => 'all',
                    'Achievement Hunter' => 'achievement-hunter',
                    'Camp Camp' => 'camp-camp',
                    'Cow Chop' => 'cow-chop',
                    'Death Battle' => 'death-battle',
                    'Friends of RT' => 'friends-of-rt',
                    'Funhaus' => 'funhaus',
                    'Inside Gaming' => 'inside-gaming',
                    'JT Music' => 'jt-music',
                    'Kinda Funny' => 'kinda-funny',
                    'Red vs. Blue Universe' => 'red-vs-blue-universe',
                    'Rooster Teeth' => 'rooster-teeth',
                    'RWBY Universe' => 'rwby-universe',
                    'Squad Team Force' => 'squad-team-force',
                    'Sugar Pine 7' => 'sugar-pine-7',
                    'The Yogscast' => 'the-yogscast',
                ]
            ],
            'sort' => [
                'type' => 'list',
                'name' => 'Sort',
                'title' => 'Select a sort order',
                'values' => [
                    'Newest -> Oldest' => 'desc',
                    'Oldest -> Newest' => 'asc'
                ],
                'defaultValue' => 'desc'
            ],
            'first' => [
                'type' => 'list',
                'name' => 'RoosterTeeth First',
                'title' => 'Select whether to include "First" videos before they are public',
                'values' => [
                    'True' => true,
                    'False' => false
                ]
            ],
            'episodeImage' => [
                'name' => 'Episode Image',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
                'title' => 'Select whether to include an episode image (if available)',
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => false,
                'title' => 'Maximum number of items to return',
                'defaultValue' => 10
            ]
        ]
    ];

    public function collectData()
    {
        if ($this->getInput('channel') !== 'all') {
            $uri = self::API
                . 'api/v1/episodes?per_page='
                . $this->getInput('limit')
                . '&channel_id='
                . $this->getInput('channel')
                . '&order=' . $this->getInput('sort')
                . '&page=1';

            $htmlJSON = getSimpleHTMLDOM($uri);
        } else {
            $uri = self::API
                . '/api/v1/episodes?per_page='
                . $this->getInput('limit')
                . '&filter=all&order='
                . $this->getInput('sort')
                . '&page=1';

            $htmlJSON = getSimpleHTMLDOM($uri);
        }

        $htmlArray = json_decode($htmlJSON, true);

        foreach ($htmlArray['data'] as $key => $value) {
            $item = [];

            if (!$this->getInput('first') && $value['attributes']['is_sponsors_only']) {
                continue;
            }

            $publicDate = date_create($value['attributes']['member_golive_at']);
            $dateDiff = date_diff($publicDate, date_create(), false);

            if (!$this->getInput('first') && $dateDiff->invert == 1) {
                continue;
            }

            $item['uri'] = self::URI . $value['canonical_links']['self'];
            $item['title'] = $value['attributes']['title'];
            $item['timestamp'] = $value['attributes']['member_golive_at'];
            $item['author'] = $value['attributes']['show_title'];
            $item['content'] = $this->getItemContent($value);

            $this->items[] = $item;
        }
    }

    protected function getItemContent(array $value): string
    {
        $content = nl2br($value['attributes']['description']);

        if (isset($value['attributes']['length'])) {
            $duration_format = $value['attributes']['length'] > 3600 ? 'G:i:s' : 'i:s';
            $content = sprintf(
                'Duration: %s<br><br>%s',
                gmdate($duration_format, $value['attributes']['length']),
                $content
            );
        }

        if ($this->getInput('episodeImage') === true) {
            foreach ($value['included']['images'] ?? [] as $image) {
                if ($image['type'] == 'episode_image') {
                    $content = sprintf(
                        '<img src="%s"/><br><br>%s',
                        $image['attributes']['medium'],
                        $content,
                    );
                    break;
                }
            }
        }

        return $content;
    }
}
