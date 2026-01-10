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
                'required' => true,
                'title' => 'Name of the orgarnization/user: https://github.com/<org>/...'

            ],
            'repository' => [
                'type' => 'text',
                'name' => 'GitHub repository',
                'exampleValue' => 'rss-bridge',
                'required' => false,
                'title' => 'Name of the repository: https://github.com/<org>/<repository>'
            ],
            'packagename' => [
                'type' => 'text',
                'name' => 'Package name',
                'exampleValue' => 'rss-bridge',
                'required' => false,
                'title' => 'Name of the package. Example "rss-bridge" or "curl-container/curl".'
            ],
            'packagetype' => [
                'type' => 'list',
                'name' => 'Package Type',
                'values' => [
                    'All' => 'all',
                    'Container' => 'container',
                    'Maven' => 'maven',
                    'npm' => 'npm',
                    'NuGet' => 'nuget',
                    'RubyGems' => 'rubygems'
                ],
                'defaultValue' => 'container',
                'required' => true,
                'title' => 'Type of package. Do not select "All" if a package name is provided.'
            ]
        ]
    ];

    private function getRepoUri()
    {
        if (!empty($this->getInput('organization')) && !empty($this->getInput('repository'))) {
            return self::URI
                . urlencode($this->getInput('organization'))
                . '/'
                . urlencode($this->getInput('repository'));
        } elseif (!empty($this->getInput('organization'))) {
            return self::URI
                . urlencode($this->getInput('organization'));
        } else {
            return 'https://github.com/RSS-Bridge/rss-bridge';
        }
    }

    private function getPackageUri()
    {
        if (!empty($this->getInput('organization')) && !empty($this->getInput('repository')) && !empty($this->getInput('packagename'))) {
            return self::URI
                . urlencode($this->getInput('organization'))
                . '/'
                . urlencode($this->getInput('repository'))
                . '/pkgs/'
                . urlencode($this->getInput('packagetype'))
                . '/'
                . urlencode($this->getInput('packagename'))
                . '/versions?filters[version_type]=tagged';
        } elseif (!empty($this->getInput('organization')) && !empty($this->getInput('repository'))) {
            return self::URI
                . 'orgs/'
                . urlencode($this->getInput('organization'))
                . '/packages?repo_name='
                . urlencode($this->getInput('repository'))
                . '&ecosystem='
                . urlencode($this->getInput('packagetype'));
        } elseif (!empty($this->getInput('organization'))) {
            return self::URI
                . 'orgs/'
                . urlencode($this->getInput('organization'))
                . '/packages?ecosystem='
                . urlencode($this->getInput('packagetype'));
        } else {
            return 'https://github.com/RSS-Bridge/rss-bridge/pkgs/container/rss-bridge/versions?filters%5Bversion_type%5D=tagged';
        }
    }

    public function collectData()
    {
        /*
          First an image of the repo is retrieved from the repo Uri or organization Uri if no repository is provided
          A Uri to this image is listed in the content of a <meta> with property set to "og:image".
          This Uri is added as the enclosures item parameter.

          When only the organization / user is provided, the bridge will list all (or for a specific type ) packages
          for the organization. If the repository is also provided, the bridge will only list the packages of the
          specified type (or all). In this case the packages are listed in a <div> with class set to "flex-auto".
          An <a>-link within this <div> and with the class set to: "text-bold f4 Link--primary" contains the link.
          Another <relative-time> with class set to: "no-wrap" in the <div> contains the creation date of the package.
          The strtotime function converts the UTC time string to local time.

          When also the packagename is provided, the bridge will show the versions for the package.
          The package versions are listed in a <div> with the class set to "col-10 d-flex flex-auto flex-column".
          Within the div the <a>-link to the package versions has the following class set: "Label mr-1 mb-2 text-normal".
          The package "latest" does not seem to have this specific class and is therefor filtered out.
          The link text is generally the Label of the package. Sometime nice labels, like 2026-01-02,
          but sometimes just a SHA value.
          There is a time value in the form of "Published (about) <#> <hours|days|month> ago in a <small> html entry.
          This <small> has the class set to: "class=color-fg-muted". The strtotime functions sets this to a timestamp.
        */

        $repoDom = getSimpleHTMLDOM($this->getRepoUri());
        $meta = $repoDom->find('meta[property=og:image]');
        $image = $meta[0]->content;

        $dom = getSimpleHTMLDOM($this->getPackageUri());

        if (empty($this->getInput('packagename'))) {
            $divs = $dom->find('div[class=flex-auto]');
            foreach ($divs as $div) {
                $a = ($div->find('a[class=text-bold f4 Link--primary]'))[0];
                $published = ($div->find('relative-time[class=no-wrap]'))[0];
                $this->items[] = [
                    'title' => $a->plaintext,
                    'uri' => 'https://github.com' . $a->href,
                    'uid' => $a->href,
                    'timestamp' => strtotime($published->datetime),
                    'enclosures' => [$image . '#.image']
                ];
            }
        } else {
            $divs = $dom->find('div[class=col-10 d-flex flex-auto flex-column]');
            foreach ($divs as $div) {
                $a = ($div->find('a[class=Label mr-1 mb-2 text-normal]'))[0];
                $published = ($div->find('small[class=color-fg-muted]'))[0];
                if (!preg_match('/[0-9]+ (hour|hours|day|days|week|weeks|month|months|year|years) ago/', $published, $ago)) {
                    $ago = [
                        'now'
                    ];
                }
                $this->items[] = [
                    'title' => $a->plaintext,
                    'uri' => 'https://github.com' . $a->href,
                    'uid' => $a->href,
                    'timestamp' => strtotime($ago[0]),
                    'enclosures' => [$image . '#.image']
                ];
            }
        }
    }

    public function getName()
    {
        $packagetype = $this->getInput('packagetype');
        if ($this->getInput('organization')) {
            $org = $this->getInput('organization');
            if ($this->getInput('repository')) {
                $repo = $this->getInput('repository');
                if ($this->getInput('packagename')) {
                    $packagename = $this->getInput('packagename');
                    return $org . '/' . $repo . ' - ' . $packagename . ' - GitHub ' . $packagetype . ' Package versions';
                }
                if ($packagetype === 'all') {
                    return $org . '/' . $repo . ' - GitHub Packages';
                }
                return $org . '/' . $repo . ' - GitHub ' . $packagetype . ' Packages';
            }
            if ($packagetype === 'all') {
                return $org . ' - GitHub Packages';
            }
            return $org . ' - GitHub ' . $packagetype . ' Packages';
        }
        return parent::getName();
    }

    public function getUri()
    {
        $packagetype = $this->getInput('packagetype');
        if ($this->getInput('organization')) {
            $org = $this->getInput('organization');
            if ($this->getInput('repository')) {
                $repo = $this->getInput('repository');
                if ($this->getInput('packagename')) {
                    $packagename = $this->getInput('packagename');
                    if ($packagetype) {
                        return self::URI . $org . '/' . $repo . '/pkgs/' . $packagetype . '/' . $packagename;
                    }
                    return self::URI . 'orgs/' . $org . '/packages?repo_name=' . $repo;
                }
                return self::URI . 'orgs/' . $org . '/packages?repo_name=' . $repo;
            }
            return self::URI . 'orgs/' . $org . '/packages';
        }
        return self::URI;
    }
}
