<?php

class GithubPackagesBridge extends BridgeAbstract
{
    const NAME = 'GitHub Packages Bridge';
    const MAINTAINER = 'rsd76';
    const URI = 'https://github.com/';
    const DESCRIPTION = 'List GitHub packaes for organization, user or project';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        [
            'organization' => [
                'type' => 'text',
                'name' => 'GitHub organization/user',
                'exampleValue' => 'RSS-Bridge',
                'required' => true
            ],
            'repository' => [
                'type' => 'text',
                'name' => 'GitHub repository',
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
                    'Container' => 'container',
                    'Maven' => 'maven',
                    'npm' => 'npm',
                    'NuGet' => 'nuget',
                    'RubyGems' => 'rubygems'
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
        /*
          The packages are listed in a <div> with the class set to "col-10 d-flex flex-auto flex-column".
          Within the div the <a>-link to the packages has the following class set: "Label mr-1 mb-2 text-normal".
          The package "latest" does not seem to have this specific class and is therefor filtered out.
          The link text is generally the Label of the package. Sometime nice labels, like 2026-01-02,
          but sometimes SHA values.
          There is a time value in the form of "Published (about) <#> <hours|days|month> ago in a small html entry.
          This small has the class set to: "class=color-fg-muted". The strtotime functions sets this to a timestamp.
        */

        $dom = getSimpleHTMLDOM($this->getPackageUri());
        // Get specific "divs" from html code.
        $divs = $dom->find('div[class=col-10 d-flex flex-auto flex-column]');
        foreach ($divs as $div) {
            $a = $div->find('a[class=Label mr-1 mb-2 text-normal]');
            $link = $a[0];
            $small = $div->find('small[class=color-fg-muted]');
            $published = $small[0];
            if (!preg_match('/[0-9]+ (hour|hours|day|days|week|weeks|month|months|year|years) ago/', $published, $ago)) {
                $ago = [
                    'now'
                ];
            }
            $this->items[] = [
                'title' => $link->plaintext,
                'uri' => 'https://github.com' . $link->href,
                'uid' => $link->href,
                'timestamp' => strtotime($ago[0])
            ];
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
