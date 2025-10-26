<?php

class GithubReleasesBridge extends BridgeAbstract
{
    const NAME = 'Github Releases Bridge';
    const MAINTAINER = 'rsd76';
    const URI = 'https://github.com';
    const APIURI = 'https://api.github.com';
    const APIVERSION = '2022-11-28';
    const DESCRIPTION = 'Bridge for getting GitHub releases';
    const CACHE_TIMEOUT = 15;

    const PARAMETERS = [
        'Github organization/repository' => [
            'organization' => [
                'type' => 'text',
                'name' => 'Github organization',
                'exampleValue' => 'RSS-Bridge'
            ],
            'repository' => [
                'type' => 'text',
                'name' => 'Github repository',
                'exampleValue' => 'rss-bridge'
            ]
        ]
    ];

    public function getApiURI()
    {
        if (!is_null($this->getInput('organization')) && !is_null($this->getInput('repository'))) {
            return self::APIURI
                . '/repos/'
                . urlencode($this->getInput('organization'))
                . '/'
                . urlencode($this->getInput('repository'))
                . '/releases';
        } else {
            return 'https://api.github.com/repos/RSS-Bridge/rss-bridge/releases';
        }
    }

    public function collectData()
    {
        $header = [
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: ' . self::APIVERSION
        ];
        $opts = [
            CURLOPT_FOLLOWLOCATION => 1
        ];
        $json = getContents($this->getApiURI(), $header, $opts);
        $releases = Json::decode($json, false);
        foreach ($releases as $release) {
            $this->items[] = [
                'uri' => $release->html_url,
                'title' => $release->name,
                'timestamp' => $release->published_at,
                'author' => $release->author->login,
                'content' => markdownToHtml($release->body),
                'uid' => strval($release->id),
            ];
        }
    }

    public function getName()
    {
        if ($this->getInput('organization')) {
            $org = $this->getInput('organization');
            if ($this->getInput('repository')) {
                $repo = $this->GetInput('repository');
                return $org . ' - ' . $repo . ' - GitHub Releases';
            }
            return $org . ' - GitHub Releases';
        }
        return parent::getName();
    }

    public function getUri()
    {
        $org = $this->getInput('organization');
        $repo = $this->GetInput('repository');
        if ($org) {
            if ($repo) {
                return 'https://github.com/' . $org . '/' . $repo . '/releases';
            }
            return 'https://github.com/' . $org;
        }
        return 'https://github.com';
    }
}
