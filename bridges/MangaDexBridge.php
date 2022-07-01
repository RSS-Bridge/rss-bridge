<?php

class MangaDexBridge extends BridgeAbstract
{
    const NAME = 'MangaDex Bridge';
    const URI = 'https://mangadex.org/';
    const API_ROOT = 'https://api.mangadex.org/';
    const DESCRIPTION = 'Returns MangaDex items using the API';

    const PARAMETERS = [
        'global' => [
            'limit' => [
                'name' => 'Item Limit',
                'type' => 'number',
                'defaultValue' => 10,
                'required' => true
            ],
            'lang' => [
                'name' => 'Chapter Languages (default=all)',
                'title' => 'comma-separated, two-letter language codes (example "en,jp")',
                'exampleValue' => 'en,jp',
                'required' => false
            ],
        ],
        'Title Chapters' => [
            'url' => [
                'name' => 'URL to title page',
                'exampleValue' => 'https://mangadex.org/title/f9c33607-9180-4ba6-b85c-e4b5faee7192/official-test-manga',
                'required' => true
            ],
            'external' => [
                'name' => 'Allow external feed items',
                'type' => 'checkbox',
                'title' => 'Some chapters are inaccessible or only available on an external site. Include these?'
            ]
        ],
        'Search Chapters' => [
            'chapter' => [
                'name' => 'Chapter Number (default=all)',
                'title' => 'The example value finds the newest first chapters',
                'exampleValue' => 1,
                'required' => false
            ],
            'groups' => [
                'name' => 'Group UUID (default=all)',
                'title' => 'This can be found in the MangaDex Group Page URL',
                'exampleValue' => '00e03853-1b96-4f41-9542-c71b8692033b',
                'required' => false,
            ],
            'uploader' => [
                'name' => 'User UUID (default=all)',
                'title' => 'This can be found in the MangaDex User Page URL',
                'exampleValue' => 'd2ae45e0-b5e2-4e7f-a688-17925c2d7d6b',
                'required' => false,
            ],
            'external' => [
                'name' => 'Allow external feed items',
                'type' => 'checkbox',
                'title' => 'Some chapters are inaccessible or only available on an external site. Include these?'
            ]
        ]
        // Future Manga Contexts:
        // Manga List (by author or tags): https://api.mangadex.org/swagger.html#/Manga/get-search-manga
        // Random Manga: https://api.mangadex.org/swagger.html#/Manga/get-manga-random
        // Future Chapter Contexts:
        // User Lists https://api.mangadex.org/swagger.html#/Feed/get-list-id-feed
        //
        // https://api.mangadex.org/docs/get-covers/
    ];

    const TITLE_REGEX = '#title/(?<uuid>[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})#';

    protected $feedName = '';
    protected $feedURI = '';

    protected function buildArrayQuery($name, $array)
    {
        $query = '';
        foreach ($array as $item) {
            $query .= '&' . $name . '=' . $item;
        }
        return $query;
    }

    protected function getAPI()
    {
        $params = [
            'limit' => $this->getInput('limit')
        ];

        $array_params = [];
        if (!empty($this->getInput('lang'))) {
            $array_params['translatedLanguage[]'] = explode(',', $this->getInput('lang'));
        }

        switch ($this->queriedContext) {
            case 'Title Chapters':
                preg_match(self::TITLE_REGEX, $this->getInput('url'), $matches)
                or returnClientError('Invalid URL Parameter');
                $this->feedURI = self::URI . 'title/' . $matches['uuid'];
                $params['order[readableAt]'] = 'desc';
                if (!$this->getInput('external')) {
                    $params['includeFutureUpdates'] = '0';
                }
                $array_params['includes[]'] = ['manga', 'scanlation_group', 'user'];
                $uri = self::API_ROOT . 'manga/' . $matches['uuid'] . '/feed';
                break;
            case 'Search Chapters':
                $params['chapter'] = $this->getInput('chapter');
                $params['groups[]'] = $this->getInput('groups');
                $params['uploader'] = $this->getInput('uploader');
                $params['order[readableAt]'] = 'desc';
                if (!$this->getInput('external')) {
                    $params['includeFutureUpdates'] = '0';
                }
                $array_params['includes[]'] = ['manga', 'scanlation_group', 'user'];
                $uri = self::API_ROOT . 'chapter';
                break;
            default:
                returnServerError('Unimplemented Context (getAPI)');
        }

        // Remove null keys
        $params = array_filter($params, function ($v) {
            return !empty($v);
        });

        $uri .= '?' . http_build_query($params);

        // Arrays are passed as repeated keys to MangaDex
        // This cannot be handled by http_build_query
        foreach ($array_params as $name => $array_param) {
            $uri .= $this->buildArrayQuery($name, $array_param);
        }

        return $uri;
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Title Chapters':
                return $this->feedName . ' Chapters';
            case 'Search Chapters':
                return 'MangaDex Chapter Search';
            default:
                return parent::getName();
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Title Chapters':
                return $this->feedURI;
            default:
                return parent::getURI();
        }
    }

    public function collectData()
    {
        $api_uri = $this->getAPI();
        $header = [
            'Content-Type: application/json'
        ];
        $content = json_decode(getContents($api_uri, $header), true);
        if ($content['result'] == 'ok') {
            $content = $content['data'];
        } else {
            returnServerError('Could not retrieve API results');
        }

        switch ($this->queriedContext) {
            case 'Title Chapters':
                $this->getChapters($content);
                break;
            case 'Search Chapters':
                $this->getChapters($content);
                break;
            default:
                returnServerError('Unimplemented Context (collectData)');
        }
    }

    protected function getChapters($content)
    {
        foreach ($content as $chapter) {
            $item = [];
            $item['uid'] = $chapter['id'];
            $item['uri'] = self::URI . 'chapter/' . $chapter['id'];

            // External chapter
            if (!$this->getInput('external') && $chapter['attributes']['pages'] == 0) {
                continue;
            }

            $item['title'] = '';
            if (isset($chapter['attributes']['volume'])) {
                $item['title'] .= 'Volume ' . $chapter['attributes']['volume'] . ' ';
            }
            if (isset($chapter['attributes']['chapter'])) {
                $item['title'] .= 'Chapter ' . $chapter['attributes']['chapter'];
            }
            if (!empty($chapter['attributes']['title'])) {
                $item['title'] .= ' - ' . $chapter['attributes']['title'];
            }
            $item['title'] .= ' [' . $chapter['attributes']['translatedLanguage'] . ']';

            $item['timestamp'] = $chapter['attributes']['readableAt'];

            $groups = [];
            $users = [];
            foreach ($chapter['relationships'] as $rel) {
                switch ($rel['type']) {
                    case 'scanlation_group':
                        $groups[] = $rel['attributes']['name'];
                        break;
                    case 'manga':
                        if (empty($this->feedName)) {
                            $this->feedName = reset($rel['attributes']['title']);
                        }
                        if ($this->queriedContext !== 'Title Chapters') {
                            $item['title'] = reset($rel['attributes']['title']) . ' ' . $item['title'];
                        }
                        break;
                    case 'user':
                        if (isset($item['author'])) {
                            $users[] = $rel['attributes']['username'];
                        } else {
                            $item['author'] = $rel['attributes']['username'];
                        }
                        break;
                }
            }
            $item['content'] = 'Groups: ' .
                             (empty($groups) ? 'No Group' : implode(', ', $groups));
            if (!empty($users)) {
                $item['content'] .= '<br>Other Users: ' . implode(', ', $users);
            }

            $this->items[] = $item;
        }
    }
}
