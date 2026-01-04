<?php

class AlpinePackagesBridge extends BridgeAbstract
{
    const NAME = 'Alpine Packages Bridge';
    const MAINTAINER = 'rsd76';
    const URI = 'https://pkgs.alpinelinux.org';
    const DESCRIPTION = 'Bridge for getting Alpine packages';
    const CACHE_TIMEOUT = 15;

    const PARAMETERS = [
        'Alpine package' => [
            'branch' => [
                'type' => 'text',
                'name' => 'Package branch',
                'exampleValue' => 'v3.22'
            ],
            'repository' => [
                'type' => 'text',
                'name' => 'Apline repository',
                'exampleValue' => 'All'
            ],
            'architecture' => [
                'type' => 'text',
                'name' => 'Achitecture',
                'exampleValue' => 'aarch64'
            ],
            'package' => [
                'type' => 'text',
                'name' => 'Package',
                'exampleValue' => 'curl'
            ]
        ]
    ];

    private function getPackageUri()
    {
        if (!is_null($this->getInput('branch')) && !is_null($this->getInput('repository')) && !is_null($this->getInput('architecture')) && !is_null($this->getInput('package'))) {
            return $this->getUri();
        } else {
            return self::URI . '/packages?name=curl&branch=v3.22&repo=&arch=aarch64&origin=&flagged=&maintainer=';
        }
    }

    public function collectData()
    {
        $dom = getSimpleHTMLDOM($this->getPackageUri());
        $divs = $dom->find('div[class=table-responsive]');
        foreach ($divs as $div) {
            $packages = $div->find('td[class=package]');
            $titles = [];
            $links = [];
            $versions = [];
            $i = 0;
            foreach ($packages as $package) {
                $ahrefs = $package->find('a');
                foreach ($ahrefs as $ahref) {
                    $titles[$i] = trim($ahref->plaintext);
                    $links[$i] = trim($ahref->href);
                    $i++;
                }
            }
            $maxi = $i;
            $i = 0;
            $versions = $div->find('td[class=version]');
            foreach ($versions as $version) {
                $strongs = $version->find('strong[class=hint--right hint--rounded text-success]');
                foreach ($strongs as $strong) {
                    $versions[$i] = trim($strong->plaintext);
                    $i++;
                }
            }
            for ($i = 0; $i < $maxi; $i++) {
                $this->items[] = [
                    'title' => $titles[$i] . '-' . $versions[$i],
                    'uri' => self::URI . $links[$i],
                    'uid' => $titles[$i] . '-' . $versions[$i]
                ];
            }
        }
    }

    public function getName()
    {
        if ($this->getInput('package')) {
            $package = $this->getInput('package');
            return 'Alpine package - ' . $package;
        }
        return parent::getName();
    }

    public function getUri()
    {
        $name = urlencode($this->getInput('package'));
        $branch = urlencode($this->GetInput('branch'));
        $repo = urlencode($this->GetInput('repository'));
        $arch = urlencode($this->GetInput('architecture'));
        if ($repo == 'All') {
            $repo = '';
        }
        if ($name && $branch && $arch) {
            return self::URI . '/packages?name=' . $name . '&branch=' . $branch . '&repo=' . $repo . '&arch=' . $arch . '&origin=&flagged=&maintainer=';
        }
        return self::URI . '/packages';
    }
}
