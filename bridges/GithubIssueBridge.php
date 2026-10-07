<?php

declare(strict_types=1);

class GithubIssueBridge extends BridgeAbstract
{
    const MAINTAINER = 'dvikan';
    const NAME = 'Github Issue';
    const URI = 'https://github.com/';
    const CACHE_TIMEOUT = 3600 * 24; // 24h
    const DESCRIPTION = 'Returns the issues or comments of an issue of a github project';

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
        'Project Issues' => [
            'c' => [
                'name' => 'Show Issues Comments',
                'type' => 'checkbox'
            ],
            'q' => [
                'name' => 'NOT IN USE',
                'required' => false,
            ]
        ],
        'Issue comments' => [
            'i' => [
                'name' => 'Issue number',
                'type' => 'number',
                'exampleValue' => '2099',
                'required' => true
            ]
        ]
    ];

    public function collectData()
    {
        $client = new GithubClient($this->cache, $this->logger);

        $owner = $this->getInput('u');
        $repo = $this->getInput('p');
        $id = $this->getInput('i');

        switch ($this->queriedContext) {
            case 'Project Issues':
                $this->items = $client->fetchIssues($owner, $repo);
                break;
            case 'Issue comments':
                $this->items = $client->fetchComments($owner, $repo, $id);
                break;
        }
    }

    public function getName()
    {
        $owner = $this->getInput('u');
        $repo = $this->getInput('p');
        $id = $this->getInput('i');

        switch ($this->queriedContext) {
            case 'Project Issues':
                return 'Project issues: github.com/' . $owner . '/' . $repo;

            case 'Issue comments':
                return 'Issue comments: github.com/' . $owner . '/' . $repo . '/issues/' . $id;

            default:
                return parent::getName();
        }
    }

    public function getURI()
    {
        $owner = $this->getInput('u');
        $repo = $this->getInput('p');
        $id = $this->getInput('i');

        switch ($this->queriedContext) {
            case 'Project Issues':
                return sprintf('https://github.com/%s/%s/issues', $owner, $repo);

            case 'Issue comments':
                return sprintf('https://github.com/%s/%s/issues/%s', $owner, $repo, $id);

            default:
                return parent::getURI();
        }
    }
}
