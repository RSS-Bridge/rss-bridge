<?php

class NvidiaDriverBridge extends FeedExpander
{
    const NAME = 'Nvidia Linux Driver Releases';
    const URI = 'https://www.nvidia.com/Download/processFind.aspx';
    const DESCRIPTION = 'Fetch the latest Nvidia Linux driver updates';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'whql' => [
                'name' => 'Version',
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

    public function collectData()
    {
        $whql = $this->getInput('whql');

        $parameters = [
            'lid'   => 1, // en-us
            'psid'  => 129, // GeForce
            'osid'  => 12, // Linux 64-bit
            'whql'  => $whql,
        ];

        $url = 'https://www.nvidia.com/Download/processFind.aspx?' . http_build_query($parameters);
        $dom = getSimpleHTMLDOM($url);

        foreach ($dom->find('tr#driverList') as $element) {
            $id = str_replace('img_', '', $element->find('img', 0)->id);

            $this->items[] = [
                'timestamp' => $element->find('td.gridItem', 3)->plaintext,
                'title'     => sprintf('Nvidia Linux Driver %s', $element->find('td.gridItem', 2)->plaintext),
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
        return sprintf('Nvidia %s Linux Driver Releases', $version);
    }
}
