<?php

class SoundCloudBridge extends BridgeAbstract
{
    const MAINTAINER = 'kranack, Roliga';
    const NAME = 'Soundcloud Bridge';
    const URI = 'https://soundcloud.com/';
    const CACHE_TIMEOUT = 600; // 10min
    const DESCRIPTION = 'Returns 10 newest music from user profile';

    const PARAMETERS = [[
        'u' => [
            'name' => 'username',
            'exampleValue' => 'thekidlaroi',
            'required' => true
        ],
        't' => [
            'name' => 'Content',
            'type' => 'list',
            'defaultValue' => 'tracks',
            'values' => [
                'All (except likes)' => 'all',
                'Tracks' => 'tracks',
                'Albums' => 'albums',
                'Playlists' => 'playlists',
                'Reposts' => 'reposts',
                'Likes' => 'likes'
            ]
        ]
    ]];

    private $apiUrl = 'https://api-v2.soundcloud.com/';
    // Without url=http, player URL returns a 404
    private $playerUrl = 'https://w.soundcloud.com/player/?url=http';
    private $widgetUrl = 'https://widget.sndcdn.com/';

    private $feedTitle = null;
    private $feedIcon = null;
    private $clientIDCache = null;

    private $clientIdRegex = '/client_id.*?"(.+?)"/';
    private $widgetRegex = '/widget-.+?\.js/';

    public function collectData()
    {
        $res = $this->getUser($this->getInput('u'));

        $this->feedTitle = $res->username;
        $this->feedIcon = $res->avatar_url;

        $apiItems = $this->getUserItems($res->id, $this->getInput('t'))
            or returnServerError('No results for ' . $this->getInput('t'));

        $hasTrackObject = ['all', 'reposts', 'likes'];

        foreach ($apiItems->collection as $index => $apiItem) {
            if (in_array($this->getInput('t'), $hasTrackObject) === true) {
                $apiItem = $apiItem->playlist ?? $apiItem->track;
            }

            $item = [];
            $item['author'] = $apiItem->user->username;
            $item['title'] = $apiItem->user->username . ' - ' . $apiItem->title;
            $item['timestamp'] = strtotime($apiItem->created_at);

            $description = nl2br($apiItem->description);

            $item['content'] = <<<HTML
				<p>{$description}</p>
HTML;

            if (isset($apiItem->tracks) && $apiItem->track_count > 0) {
                $list = $this->getTrackList($apiItem->tracks);

                $item['content'] .= <<<HTML
					<p><strong>Tracks ({$apiItem->track_count})</strong></p>
					{$list}
HTML;
            }

            $item['enclosures'][] = $apiItem->artwork_url;
            $item['id'] = $apiItem->permalink_url;
            $item['uri'] = $apiItem->permalink_url;
            $this->items[] = $item;

            if (count($this->items) >= 10) {
                break;
            }
        }
    }

    public function getIcon()
    {
        if ($this->feedIcon) {
            return $this->feedIcon;
        }

        return parent::getIcon();
    }

    public function getURI()
    {
        if ($this->getInput('u')) {
            return self::URI . $this->getInput('u') . '/' . $this->getInput('t');
        }

        return parent::getURI();
    }

    public function getName()
    {
        if ($this->feedTitle) {
            return $this->feedTitle . ' - ' . ucfirst($this->getInput('t')) . ' - ' . self::NAME;
        }

        return parent::getName();
    }

    private function initClientIDCache()
    {
        if ($this->clientIDCache !== null) {
            return;
        }

        $cacheFactory = new CacheFactory();

        $this->clientIDCache = $cacheFactory->create();
        $this->clientIDCache->setScope('SoundCloudBridge');
        $this->clientIDCache->setKey(['client_id']);
    }

    private function getClientID()
    {
        $this->initClientIDCache();

        $clientID = $this->clientIDCache->loadData();

        if ($clientID == null) {
            return $this->refreshClientID();
        } else {
            return $clientID;
        }
    }

    private function refreshClientID()
    {
        $this->initClientIDCache();

        $playerHTML = getContents($this->playerUrl);

        // Extract widget JS filenames from player page
        if (preg_match_all($this->widgetRegex, $playerHTML, $matches) == false) {
            returnServerError('Unable to find widget JS URL.');
        }

        $clientID = '';

        // Loop widget js files and extract client ID
        foreach ($matches[0] as $widgetFile) {
            $widgetURL = $this->widgetUrl . $widgetFile;

            $widgetJS = getContents($widgetURL);

            if (preg_match($this->clientIdRegex, $widgetJS, $matches)) {
                $clientID = $matches[1];
                $this->clientIDCache->saveData($clientID);

                return $clientID;
            }
        }

        if (empty($clientID)) {
            returnServerError('Unable to find client ID.');
        }
    }

    private function buildApiUrl($endpoint, $parameters)
    {
        return $this->apiUrl
            . $endpoint
            . '?'
            . http_build_query($parameters);
    }

    private function getUser($username)
    {
        $parameters = ['url' => self::URI . $username];

        return $this->getApi('resolve', $parameters);
    }

    private function getUserItems($userId, $type)
    {
        $parameters = ['limit' => 10];
        $endpoint = 'users/' . $userId . '/' . $type;

        if ($type === 'playlists') {
            $endpoint = 'users/' . $userId . '/playlists_without_albums';
        }

        if ($type === 'all') {
            $endpoint = 'stream/users/' . $userId;
        }

        if ($type === 'reposts') {
            $endpoint = 'stream/users/' . $userId . '/' . $type;
        }

        return $this->getApi($endpoint, $parameters);
    }

    private function getApi($endpoint, $parameters)
    {
        $parameters['client_id'] = $this->getClientID();
        $url = $this->buildApiUrl($endpoint, $parameters);

        try {
            return json_decode(getContents($url));
        } catch (Exception $e) {
            // Retry once with refreshed client ID
            $parameters['client_id'] = $this->refreshClientID();
            $url = $this->buildApiUrl($endpoint, $parameters);

            return json_decode(getContents($url));
        }
    }

    private function getTrackList($tracks)
    {
        $trackids = '';

        foreach ($tracks as $track) {
            $trackids .= $track->id . ',';
        }

        $apiItems = $this->getApi(
            'tracks',
            ['ids' => $trackids]
        );

        $list = '';
        foreach ($apiItems as $track) {
            $list .= <<<HTML
				<li>{$track->user->username} — <a href="{$track->permalink_url}">{$track->title}</a></li>
HTML;
        }

        $html = <<<HTML
			<ul>{$list}</ul>
HTML;

        return $html;
    }
}
