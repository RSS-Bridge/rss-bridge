<?php

class DemoBridge extends BridgeAbstract
{
    const MAINTAINER = 'teromene';
    const NAME = 'DemoBridge';
    const URI = 'https://github.com/rss-bridge/rss-bridge';
    const DESCRIPTION = 'Bridge used for demos';
    const CACHE_TIMEOUT = 15;

    const PARAMETERS = [
        'testCheckbox' => [
            'testCheckbox' => [
                'type' => 'checkbox',
                'name' => 'test des checkbox'
            ]
        ],
        'testList' => [
            'testList' => [
                'type' => 'list',
                'name' => 'test des listes',
                'values' => [
                    'Test' => 'test',
                    'Test 2' => 'test2'
                ]
            ]
        ],
        'testNumber' => [
            'testNumber' => [
                'type' => 'number',
                'name' => 'test des numéros',
                'exampleValue' => '1515632'
            ]
        ]
    ];

    public function collectData()
    {
        $item = [];
        $item['author'] = 'Me!';
        $item['title'] = 'Test';
        $item['content'] = 'Awesome content !';
        $item['id'] = 'Lalala';
        $item['uri'] = 'http://example.com/test';

        $this->items[] = $item;
    }
}
