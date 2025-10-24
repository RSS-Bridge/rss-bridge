<?php

declare(strict_types=1);

class ComboiosDePortugalBridge extends BridgeAbstract
{
    const NAME = 'CP | Avisos';
    const URI = 'https://www.cp.pt';
    const DESCRIPTION = 'Comboios de Portugal | Avisos';
    const MAINTAINER = 'FJSFerreira';

    const PARAMETERS = [
        [
            'language' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'Português' => 'pt-PT',
                    'English' => 'en-US'
                ],
                'defaultValue' => 'pt-PT'
            ],
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'All categories' => 0,
                    'Alfa Pendular' => 50540,
                    'Intercidades' => 57687,
                    'Internacional' => 57690,
                    'Regional' => 57693,
                    'Turísticos / Históricos' => 57696,
                    'Urbanos de Coimbra' => 57699,
                    'Urbanos de Lisboa' => 57702,
                    'Urbanos do Porto' => 57705
                ],
                'defaultValue' => 0
            ]
        ]
    ];

    public function collectData()
    {
        $json = getContents(self::URI . '/bei/getContentsList?path=PWA/Homepage/Avisos&order=dateModified:desc&categoryId=' . $this->getInput('category'));

        $data = Json::decode($json);

        foreach ($data['item'] as $entry) {
            $item = [];

            // language defaults to portuguese
            $item['title'] = $entry['title'][$this->getInput('language')] ?? $entry['title'][$this->getInput('pt-PT')];
            $item['uri'] = self::URI . '/pt/detalhe-aviso/' . $entry['friendlyUrlPath'];
            $item['timestamp'] = $entry['dateModified'];
            $item['content'] = $entry['description'][$this->getInput('language')] ?? $entry['description'][$this->getInput('pt-PT')];

            $this->items[] = $item;
        }
    }
}
