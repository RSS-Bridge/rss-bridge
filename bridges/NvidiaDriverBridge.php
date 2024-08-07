<?php

class NvidiaDriverBridge extends FeedExpander
{
    const NAME = 'NVIDIA Linux Driver Releases';
    const URI = 'https://www.nvidia.com/Download/processFind.aspx';
    const DESCRIPTION = 'Fetch the latest NVIDIA Linux driver updates';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'whql' => [
                'name' => 'Version',
                'type' => 'list',
                'values' => [
                        'All' => '',
                        'Beta' => '0',
                        'New Feature Branch' => '5',
                        'Recommended/Certified' => '1',
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
                'title'     => sprintf('NVIDIA Linux Driver %s', $element->find('td.gridItem', 2)->plaintext),
                'uri'       => 'https://www.nvidia.com/Download/driverResults.aspx/' . $id,
                'content'   => $dom->find('tr#tr_' . $id . ' span', 0)->innertext,
            ];
        }
    }
}
