<?php

declare(strict_types=1);

class GithubPackagesBridge extends BridgeAbstract
{
    const NAME = 'GitHub Packages Bridge';
    const MAINTAINER = 'rsd76';
    const URI = 'https://github.com/';
    const DESCRIPTION = 'List GitHub packages or versions for organization, user or project';
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
                'title' => 'Type of package. Do not select "All" if a package name is provided.'
            ]
        ]
    ];

    private function getPackageUri()
    {
        if (!empty($this->getInput('organization')) && !empty($this->getInput('repository')) && !empty($this->getInput('packagename'))) {
            if ($this->getInput('packagetype') === 'all') {
                throwClientException('Do not provide package type as "all" when specifying a package name.');
            }
        } elseif (!empty($this->getInput('organization')) && empty($this->getInput('repository')) && !empty($this->getInput('packagename'))) {
            throwClientException('Provide a repository when providing a package name or do not provide the package name.');
        } elseif (empty($this->getInput('organization'))) {
            throwClientException('Provide at least an organization.');
        }
        return $this->getUri();
    }

    public function collectData()
    {
        /*
          Use helper function defaultLinkTo to replace all relative URLs to absolute URLs.

          When only the organization / user is provided, the bridge will list all packages (or filtered to a specific type)
          for the organization. If the repository is also provided, the bridge will only list the packages of the
          specified type (or all). In this case the packages are listed in a <div> with class set to "flex-auto".
          An <a>-link within this <div> and with the class set to: "text-bold f4 Link--primary" contains the link.
          Another <relative-time> with class set to: "no-wrap" in the <div> contains the creation date of the package.
          The strtotime function converts the UTC time string to local time.

          When also the packagename is provided, the bridge will show the versions for the package.
          The package versions are listed in a <div> with the class set to "col-10 d-flex flex-auto flex-column".
          Within the div the <a>-link to the package versions has the following class set: "Label mr-1 mb-2 text-normal".
          For RubyGems the class seems to be set to: "Link--primary text-bold f4". If for classes no <a>-link is found,
          a third class is searched: "Label Label--success mr-1 mb-2". The package label "latest" is filtered out unless it is
          the only label. The link text is generally the Label of the package. Sometimes nice labels, like 2026-01-02,
          but sometimes just a SHA value.
          There is a time value in the form of "Published (about) <#> <hours|days|month> ago in a <small> html entry.
          This <small> has the class set to: "class=color-fg-muted". The strtotime functions sets this to a timestamp.
        */

        $dom = getSimpleHTMLDOM($this->getPackageUri());

        $dom = defaultLinkTo($dom, self::URI);

        if (empty($this->getInput('packagename'))) {
            $divs = $dom->find('div[class=flex-auto]');
            foreach ($divs as $div) {
                $a = ($div->find('a[class=text-bold f4 Link--primary]'))[0];
                $published = ($div->find('relative-time[class=no-wrap]'))[0];
                $this->items[] = [
                    'title' => $a->plaintext,
                    'uri' => $a->href,
                    'uid' => $a->href,
                    'timestamp' => strtotime($published->datetime)
                ];
            }
        } else {
            $divs = $dom->find('div[class=col-10 d-flex flex-auto flex-column]');
            foreach ($divs as $div) {
                $a = ($div->find('a[class=Label mr-1 mb-2 text-normal]'))[0];
                if (!$a) {
                    $a = ($div->find('a[class=Link--primary text-bold f4]'))[0];
                }
                if (!$a) {
                    $a = ($div->find('a[class=Label Label--success mr-1 mb-2]'))[0];
                }
                $published = ($div->find('small[class=color-fg-muted]'))[0];
                if (!$published) {
                    $published = ($div->find('div[class=f6 color-fg-muted]'))[0];
                }
                if (preg_match('/[0-9]+ (hour|hours|day|days|week|weeks|month|months|year|years) ago/', $published->plaintext, $ago)) {
                    $this->items[] = [
                        'title' => $a->plaintext,
                        'uri' => $a->href,
                        'uid' => $a->href,
                        'timestamp' => strtotime($ago[0])
                    ];
                } else {
                    $this->items[] = [
                        'title' => $a->plaintext,
                        'uri' => $a->href,
                        'uid' => $a->href
                    ];
                }
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
            $org = urlencode($this->getInput('organization'));
            if ($this->getInput('repository')) {
                $repo = urlencode($this->getInput('repository'));
                if ($this->getInput('packagename')) {
                    $packagename = urlencode($this->getInput('packagename'));
                    if ($packagetype !== 'all') {
                        return self::URI . $org . '/' . $repo . '/pkgs/' . $packagetype . '/' . $packagename . '/versions?filters[version_type]=tagged';
                    }
                    return self::URI . 'orgs/' . $org . '/packages?repo_name=' . $repo . '&ecosystem=' . $packagetype;
                }
                return self::URI . 'orgs/' . $org . '/packages?repo_name=' . $repo . '&ecosystem=' . $packagetype;
            }
            return self::URI . 'orgs/' . $org . '/packages?ecosystem=' . $packagetype;
        }
        return self::URI;
    }
}
