<?php

class NvidiaDriverBridge extends FeedExpander
{
    const NAME = 'NVIDIA Driver Releases';
    const URI = 'https://www.nvidia.com/Download/processFind.aspx';
    const DESCRIPTION = 'Fetch the latest NVIDIA driver updates';
    const MAINTAINER = 'tillcash';

    const PARAMETERS = [
        'Windows' => [
            'whql' => [
                'name' => 'Driver Type',
                'type' => 'list',
                'values' => [
                    'Certified' => '1',
                ],
            ],
        ],
        'Linux' => [
            'whql' => [
                'name' => 'Driver Type',
                'type' => 'list',
                'values' => [
                    'All' => '',
                    'Beta' => '0',
                    'Branch' => '5',
                    'Certified' => '1',
                ],
            ],
        ],
        'FreeBSD' => [
            'whql' => [
                'name' => 'Driver Type',
                'type' => 'list',
                'values' => [
                    'All' => '',
                    'Beta' => '0',
                    'Branch' => '5',
                    'Certified' => '1',
                ],
            ],
        ],
    ];

    private $operatingSystem = '';

    public function collectData()
    {
        $whql = $this->getInput('whql');
        $parameters = [
            'lid'   => 1, // en-us
            'psid'  => 129, // GeForce
            'whql'  => $whql,
        ];

        switch ($this->queriedContext) {
            case 'Windows':
                $parameters['osid'] = 57;
                $parameters['dtcid'] = 1; // Windows Driver DCH
                $this->operatingSystem = 'Windows';
                break;
            case 'Linux':
                $parameters['osid'] = 12;
                $this->operatingSystem = 'Linux';
                break;
            case 'FreeBSD':
                $parameters['osid'] = 22;
                $this->operatingSystem = 'FreeBSD';
                break;
        }

        $url = 'https://www.nvidia.com/Download/processFind.aspx?' . http_build_query($parameters);
        $dom = getSimpleHTMLDOM($url);

        foreach ($dom->find('tr#driverList') as $element) {
            $id = str_replace('img_', '', $element->find('img', 0)->id);

            $this->items[] = [
                'timestamp' => $element->find('td.gridItem', 3)->plaintext,
                'title'     => sprintf('NVIDIA Driver %s', $element->find('td.gridItem', 2)->plaintext),
                'uri'       => 'https://www.nvidia.com/Download/driverResults.aspx/' . $id,
                'content'   => $dom->find('tr#tr_' . $id . ' span', 0)->innertext,
            ];
        }
    }

    public function getIcon()
    {
        return 'https://www.nvidia.com/favicon.ico';
    }

    public function getName()
    {
        $version = $this->getKey('whql') ?? '';
        return sprintf('NVIDIA %s %s Driver Releases', $this->operatingSystem, $version);
    }
}
