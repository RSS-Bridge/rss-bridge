<?php

declare(strict_types=1);

class GitHubPullRequestBridge extends BridgeAbstract
{
    const MAINTAINER = 'dvikan';
    const NAME = 'GitHub Pull Request';
    const URI = 'https://github.com/';
    const CACHE_TIMEOUT = 3600 * 24; // 24h
    const DESCRIPTION = 'Returns the pull request or comments of a pull request of a GitHub project';

    const PARAMETERS = [
        'global' => [
            'u' => [
                'name' => 'User name',
                'exampleValue' => 'RSS-Bridge',
                'required' => true
            ],
            'p' => [
                'name' => 'Project name',
                'exampleValue' => 'rss-bridge',
                'required' => true
            ]
        ],
        'Project Pull Requests' => [
            'c' => [
                'name' => 'Show Pull Request Comments',
                'type' => 'checkbox'
            ],
            'q' => [
                'name' => 'NOT IN USE',
                'required' => false,
            ]
        ],
        'Pull Request comments' => [
            'i' => [
                'name' => 'Pull Request number',
                'type' => 'number',
                'exampleValue' => '2100',
                'required' => true
            ]
        ]
    ];

    public function collectData()
    {
        $client = new GithubClient($this->cache, $this->logger);

        $owner = $this->getInput('u');
        $repo = $this->getInput('p');

        switch ($this->queriedContext) {
            case 'Project Pull Requests':
                $this->items = $client->fetchPullRequests($owner, $repo);
                break;
        }
    }
}
