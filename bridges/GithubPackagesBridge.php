<?php

class GithubPackagesBridge extends BridgeAbstract
{
    const NAME = 'Github Packages Bridge';
    const MAINTAINER = 'rsd76';
    const URI = 'https://github.com/';
    const DESCRIPTION = 'Bridge for getting GitHub packages';
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
            ],
            'packagename' => [
                'type' => 'text',
                'name' => 'Package name',
                'exampleValue' => 'rss-bridge'
            ],
            'packagetype' => [
                'type' => 'list',
                'name' => 'Package Type',
                'values' => [
                    'npm' => 'npm',
                    'maven' => 'maven',
                    'rubygems' => 'rubegems',
                    'docker' => 'docker',
                    'nuget' => 'nuget',
                    'container' => 'container'
                ],
                'defaultValue' => 'container'
            ]
        ]
    ];

    private function getPackageUri()
    {
        if (!is_null($this->getInput('organization')) && !is_null($this->getInput('repository'))) {
            return self::URI
                . urlencode($this->getInput('organization'))
                . '/'
                . urlencode($this->getInput('repository'))
                . '/pkgs/'
                . urlencode($this->getInput('packagetype'))
                . '/'
                . urlencode($this->getInput('packagename'))
                . '/versions?filters[version_type]=tagged';
        } else {
            return 'https://github.com/RSS-Bridge/rss-bridge/pkgs/container/rss-bridge/versions?filters%5Bversion_type%5D=tagged';
        }
    }

    public function collectData()
    {
        $dom = getSimpleHTMLDOM($this->getPackageUri());
        $divs = $dom->find('div[class=col-10 d-flex flex-auto flex-column]');
        foreach ($divs as $div) {
            $a = $div->find('a[class=Label mr-1 mb-2 text-normal]');
            foreach ($a as $link) {
                $this->items[] = [
                    'title' => $link->plaintext,
                    'uri' => 'https://github.com' . $link->href,
                    'uid' => $link->href
                ];
            }
        }
    }

    public function getName()
    {
        if ($this->getInput('organization')) {
            $org = $this->getInput('organization');
            if ($this->GetInput('repository')) {
                $repo = $this->GetInput('repository');
                return $org . ' - ' . $repo . ' - GitHub Packages';
            }
            return $org . ' - GitHub Packages';
        }
        return parent::getName();
    }

    public function getUri()
    {
        $org = $this->getInput('organization');
        $repo = $this->GetInput('repository');
        $packagename = $this->GetInput('packagename');
        $packagetype = $this->GetInput('packagetype');
        if ($org) {
            if ($repo) {
                if ($packagename) {
                    if ($packagetype) {
                        return 'https://github.com/' . $org . '/' . $repo . '/pkgs/' . $packagetype . '/' . $packagename;
                    }
                    return 'https://github.com/orgs/' . $org . '/packages?repo_name=' . $repo;
                }
                return 'https://github.com/orgs/' . $org . '/packages?repo_name=' . $repo;
            }
            return 'https://github.com/' . $org;
        }
        return 'https://github.com';
    }
}
