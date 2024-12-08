<?php

class WorldbankBridge extends BridgeAbstract
{
    const NAME = 'World Bank Group';
    const URI = 'https://www.worldbank.org/en/news/all';
    const DESCRIPTION = 'Return articles from The World Bank Group All News';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'lang' => [
                'name' => 'Language',
                'type' => 'list',
                'defaultValue' => 'English',
                'values' => [
                    'English' => 'English',
                    'French' => 'French',
                ]
            ],
            'limit' => [
                'name' => 'limit (max 100)',
                'type' => 'number',
                'defaultValue' => 5,
                'required' => true,
            ]
        ]
    ];

    public function collectData()
    {
        $apiUrl = 'https://search.worldbank.org/api/v2/news?format=json&rows='
            . min(100, $this->getInput('limit'))
            . '&lang_exact=' . $this->getInput('lang');

        $jsonData = json_decode(getContents($apiUrl));

        // Remove unnecessary data from the original object
        if (isset($jsonData->documents->facets)) {
            unset($jsonData->documents->facets);
        }

        foreach ($jsonData->documents as $element) {
            $this->items[] = [
                'uid' => $element->id,
                'timestamp' => $element->lnchdt,
                'title' => $element->title->{'cdata!'},
                'uri' => $element->url,
                'content' => $element->descr->{'cdata!'},
            ];
        }
    }
}
