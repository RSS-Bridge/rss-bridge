<?php

class DockerHubBridge extends BridgeAbstract
{
    const NAME = 'Docker Hub Bridge';
    const URI = 'https://hub.docker.com';
    const DESCRIPTION = 'Returns new images for a container';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [
        'User Submitted Image' => [
            'user' => [
                'name' => 'User',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'rssbridge',
            ],
            'repo' => [
                'name' => 'Repository',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'rss-bridge',
            ]
        ],
        'Official Image' => [
            'repo' => [
                'name' => 'Repository',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'postgres',
            ]
        ],
    ];

    const CACHE_TIMEOUT = 3600; // 1 hour

    private $apiURL = 'https://hub.docker.com/v2/repositories/';
    private $imageUrlRegex = '/hub\.docker\.com\/r\/([\w]+)\/([\w-]+)\/?/';
    private $officialImageUrlRegex = '/hub\.docker\.com\/_\/([\w-]+)\/?/';

    public function detectParameters($url)
    {
        $params = [];

        // user submitted image
        if (preg_match($this->imageUrlRegex, $url, $matches)) {
            $params['context'] = 'User Submitted Image';
            $params['user'] = $matches[1];
            $params['repo'] = $matches[2];
            return $params;
        }

        // official image
        if (preg_match($this->officialImageUrlRegex, $url, $matches)) {
            $params['context'] = 'Official Image';
            $params['repo'] = $matches[1];
            return $params;
        }

        return null;
    }

    public function collectData()
    {
        $json = getContents($this->getApiUrl());

        $data = json_decode($json, false);

        foreach ($data->results as $result) {
            $item = [];

            $lastPushed = date('Y-m-d H:i:s', strtotime($result->tag_last_pushed));

            $item['title'] = $result->name;
            $item['uid'] = $result->id;
            $item['uri'] = $this->getTagUrl($result->name);
            $item['author'] = $result->last_updater_username;
            $item['timestamp'] = $result->tag_last_pushed;
            $item['content'] = <<<EOD
<Strong>Tag</strong><br>
<p>{$result->name}</p>
<Strong>Last pushed</strong><br>
<p>{$lastPushed}</p>
<Strong>Images</strong><br>
{$this->getImages($result)}
EOD;

            $this->items[] = $item;
        }
    }

    public function getURI()
    {
        if ($this->queriedContext === 'Official Image') {
            return self::URI . '/_/' . $this->getRepo();
        }

        if ($this->getInput('repo')) {
            return self::URI . '/r/' . $this->getRepo();
        }

        return parent::getURI();
    }

    public function getName()
    {
        if ($this->getInput('repo')) {
            return $this->getRepo() . ' - Docker Hub';
        }

        return parent::getName();
    }

    private function getRepo()
    {
        if ($this->queriedContext === 'Official Image') {
            return $this->getInput('repo');
        }

        return $this->getInput('user') . '/' . $this->getInput('repo');
    }

    private function getApiUrl()
    {
        if ($this->queriedContext === 'Official Image') {
            return $this->apiURL . 'library/' . $this->getRepo() . '/tags/?page_size=25&page=1';
        }

        return $this->apiURL . $this->getRepo() . '/tags/?page_size=25&page=1';
    }

    private function getLayerUrl($name, $digest)
    {
        if ($this->queriedContext === 'Official Image') {
            return self::URI . '/layers/' . $this->getRepo() . '/library/' .
                $this->getRepo() . '/' . $name . '/images/' . $digest;
        }

        return self::URI . '/layers/' . $this->getRepo() . '/' . $name . '/images/' . $digest;
    }

    private function getTagUrl($name)
    {
        if ($this->queriedContext === 'Official Image') {
            return self::URI . '/_/' . $this->getRepo() . '?tab=tags&name=' . $name;
        }

        return self::URI . '/r/' . $this->getRepo() . '/tags?name=' . $name;
    }

    private function getImages($result)
    {
        $html = <<<EOD
<table style="width:300px;"><thead><tr><th>Digest</th><th>OS/architecture</th></tr></thead></tbody>
EOD;

        foreach ($result->images as $image) {
            $layersUrl = $this->getLayerUrl($result->name, $image->digest);
            $id = $this->getShortDigestId($image->digest);

            $html .= <<<EOD
			<tr>
				<td><a href="{$layersUrl}">{$id}</a></td>
				<td>{$image->os}/{$image->architecture}</td>
			</tr>
EOD;
        }

        return $html . '</tbody></table>';
    }

    private function getShortDigestId($digest)
    {
        $parts = explode(':', $digest);
        return substr($parts[1], 0, 12);
    }
}
