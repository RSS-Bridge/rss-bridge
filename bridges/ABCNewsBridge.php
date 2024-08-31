<?php

class ABCNewsBridge extends BridgeAbstract
{
    const NAME = 'ABC News Bridge';
    const URI = 'https://www.abc.net.au';
    const DESCRIPTION = 'Topics of the Australian Broadcasting Corporation';
    const MAINTAINER = 'yue-dongchen';

    const PARAMETERS = [
        [
            'topic' => [
                'type' => 'list',
                'name' => 'Region',
                'title' => 'Choose state',
                'values' => [
                    'ACT' => 'act',
                    'NSW' => 'nsw',
                    'NT' => 'nt',
                    'QLD' => 'qld',
                    'SA' => 'sa',
                    'TAS' => 'tas',
                    'VIC' => 'vic',
                    'WA' => 'wa'
                ],
            ]
        ]
    ];

    public function collectData()
    {
        $url = sprintf('https://www.abc.net.au/news/%s', $this->getInput('topic'));
        $dom = getSimpleHTMLDOM($url);
        $dom = $dom->find('div[data-component="PaginationList"]', 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('article[data-component="DetailCard"]') as $article) {
            $a = $article->find('a', 0);
            $this->items[] = [
                'title' => $a->plaintext,
                'uri' => $a->href,
                'content' => $article->find('p', 0)->plaintext,
                'timestamp' => strtotime($article->find('time', 0)->datetime),
            ];
        }
    }
}
