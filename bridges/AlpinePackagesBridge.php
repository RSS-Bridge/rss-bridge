<?php

declare(strict_types=1);

class AlpinePackagesBridge extends BridgeAbstract
{
    const NAME = 'Alpine Packages Bridge';
    const MAINTAINER = 'rsd76';
    const URI = 'https://pkgs.alpinelinux.org';
    const DESCRIPTION = 'Bridge for getting Alpine packages versions';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        [
            'package' => [
                'type' => 'text',
                'name' => 'Package Name',
                'required' => true,
                'exampleValue' => 'curl',
                'title' => 'Name of the package. Use * and ? as wildcards. Example: curl, curl-* or curl-???'
            ],
            'branch' => [
                'type' => 'text',
                'name' => 'Package branch',
                'required' => true,
                'exampleValue' => 'v3.23',
                'title' => 'Name of the branch. Like: edge, v3.23, v3.22, etc.'
            ],
            'repository' => [
                'type' => 'list',
                'name' => 'Repository name',
                'values' => [
                    'All' => 'all',
                    'Community' => 'community',
                    'Main' => 'main',
                    'Testing' => 'testing'
                ],
                'defaultValue' => 'all',
                'title' => 'Name of the repository.'
            ],
            'architecture' => [
                'type' => 'text',
                'name' => 'Achitecture',
                'exampleValue' => 'aarch64',
                'title' => 'Architecture of the package. Like: aarch64, armhf, armv7, loongarch64, ppc64le, ppc64le, ppc64le, x86 and x86_64. Keep empty or use "all" for all.'
            ]
        ]
    ];

    private function getADom($element)
    {
        return $element->find('a')[0];
    }

    private function getElementData($element)
    {
        $classes = [
            'package',
            'repo',
            'arch',
            'maintainer'
        ];
        $noAhrefClasses = [
            'branch',
            'bdate'
        ];
        $data = [];
        // Get data from element which contains <a href=...>.
        foreach ($classes as $class) {
            $td = $this->getTdClassDom($element, $class);
            $a = $this->getADom($td);
            $data[$class] = trim($a->plaintext);
            $data[$class . '-href'] = $a->href;
        }
        // Get data from element which only contains text.
        foreach ($noAhrefClasses as $class) {
            $td = $this->getTdClassDom($element, $class);
            $data[$class] = trim($td->plaintext);
        }
        // Get version data in a <strong> element.
        $td = $this->getTdClassDom($element, 'version');
        $strong = $td->find('strong[class=hint--right hint--rounded text-success]')[0];
        $data['version'] = trim($strong->plaintext);
        return $data;
    }

    private function getTdClassDom($element, $class)
    {
        return $element->find('td[class=' . $class . ']')[0];
    }

    public function collectData()
    {
        $dom = getSimpleHTMLDOM($this->getUri());
        $dom = defaultLinkTo($dom, self::URI);
        $table = $dom->find('table[class=pure-table pure-table-striped]')[0];
        $tbody = $table->find('tbody')[0];
        $trs = $tbody->find('tr');
        foreach ($trs as $tr) {
            $itemData = $this->getElementData($tr);
            $this->items[] = [
                'title' => $itemData['package'] . '-' . $itemData['version'],
                'uri' => $itemData['package-href'],
                'timestamp' => strtotime($itemData['bdate']),
                'uid' => trim($itemData['package']) . $itemData['version'] . $itemData['arch'] . $itemData['branch'] . $itemData['repo'],
                'author' => $itemData['maintainer'],
                'categories' => [
                    'arch: ' . $itemData['arch'],
                    'branch: ' . $itemData['branch'],
                    'repo: ' . $itemData['repo']
                ]
            ];
        }
    }

    public function getName()
    {
        $packageName = $this->getInput('package');
        $branchName = $this->getInput('branch');
        $repositoryName = $this->getInput('repository');
        $architecture = $this->getInput('architecture');

        $name = '';

        if ($packageName) {
            $packageName = strtolower($packageName);
            $name = $packageName;
            if ($branchName) {
                $branchName = strtolower($branchName);
                $name = $name . ' on branch ' . $branchName;
            }
            if ($repositoryName) {
                $repositoryName = strtolower($repositoryName);
                if ($repositoryName === 'all') {
                    $name = $name . ' in all repositories';
                } else {
                    $name = $name . ' in repository ' . $repositoryName;
                }
            }
            if ($architecture) {
                $architecture = strtolower($architecture);
                if ($architecture === 'all') {
                    $name = $name . ' on all architectures';
                } else {
                    $name = $name . ' on ' . $architecture;
                }
            }
            return $name;
        }

        return parent::getName();
    }

    public function getUri()
    {
        $package = $this->getInput('package');
        $branch = $this->getInput('branch');
        $repository = $this->getInput('repository');
        $architecture = $this->getInput('architecture');

        if ($package) {
            $package = urlencode(strtolower(trim($package)));
        }
        if ($branch) {
            $branch = strtolower(trim($branch));
        }
        if ($repository) {
            $repository = strtolower($repository);
            if ($repository === 'all') {
                $repository = '';
            }
        }
        if ($architecture) {
            $architecture = strtolower(trim($architecture));
            if ($architecture === 'all') {
                $architecture = '';
            }
        }

        if ($package && $branch) {
            return self::URI . '/packages?name=' . $package . '&branch=' . $branch . '&repo=' . $repository . '&arch=' . $architecture . '&origin=&flagged=&maintainer=';
        }
        return self::URI;
    }
}
