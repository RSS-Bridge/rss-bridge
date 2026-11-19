<?php

declare(strict_types=1);

class AlpinePackagesBridge extends BridgeAbstract
{
    const NAME = 'Alpine Packages';
    const MAINTAINER = 'rsd76';
    const URI = 'https://pkgs.alpinelinux.org';
    const DESCRIPTION = 'Get Alpine package versions';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [
        [
            'package' => [
                'type' => 'text',
                'name' => 'Package Name',
                'required' => true,
                'exampleValue' => 'curl',
                'title' => 'Name of the package. Use * and ? as wildcards. For example: curl-dev, curl-* or curl-???.'
            ],
            'branch' => [
                'type' => 'text',
                'name' => 'Package branch',
                'required' => true,
                'exampleValue' => 'v3.23',
                'title' => 'Name of the branch. For example: edge, v3.23, v3.22, etc.'
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
                'defaultValue' => 'all'
            ],
            'architecture' => [
                'type' => 'list',
                'name' => 'Achitecture',
                'values' => [
                    'All' => 'all',
                    'aarch64' => 'aarch64',
                    'armhf' => 'armhf',
                    'armv7' => 'armv7',
                    'loongarch64' => 'loongarch64',
                    'ppc64le' => 'ppc64le',
                    'riscv64' => 'riscv64',
                    's390x' => 's390x',
                    'x86' => 'x86',
                    'x86_64' => 'x86_64'
                ],
                'defaultValue' => 'all'
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
        // Multiple classes exist for the strong version element.
        // Text classes found in: https://pkgs.alpinelinux.org/static/css/style.css
        $strongClasses = [
             'hint--right hint--rounded text-success',
             'hint--right hint--rounded text-danger',
             'hint--right hint--rounded text-warning',
             'hint--right hint--rounded text-secondary',
             'hint--right hint--rounded text-grey',
        ];
        $strongClass = 0;
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
        // Loop to get non empty strong element
        do {
            $strong = $td->find('strong[class=' . $strongClasses[$strongClass] . ']')[0];
            $strongClass++;
        } while (!$strong && $strongClass < count($strongClasses));
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
            $name = $packageName . ' (';
            if ($branchName) {
                $branchName = strtolower($branchName);
                $name .= 'branch ' . $branchName;
            }
            if ($repositoryName) {
                $repositoryName = strtolower($repositoryName);
                if ($repositoryName !== 'all') {
                    $name .= ', repo ' . $repositoryName;
                }
            }
            if ($architecture) {
                $architecture = strtolower($architecture);
                if ($architecture !== 'all') {
                    $name .= ', arch ' . $architecture;
                }
            }
            $name .= ') - Alpine packages';
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
