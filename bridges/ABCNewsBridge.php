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
        $url = 'https://www.abc.net.au/news/' . $this->getInput('topic');
        $html = getSimpleHTMLDOM($url)->find('.YAJzu._2FvRw.ZWhbj._3BZxh', 0);
        $html = defaultLinkTo($html, $this->getURI());

        foreach ($html->find('._2H7Su') as $article) {
            $item = [];

            $title = $article->find('._3T9Id.fmhNa.nsZdE._2c2Zy._1tOey._3EOTW', 0);
            $item['title'] = $title->plaintext;
            $item['uri'] = $title->href;
            $item['content'] = $article->find('.rMkro._1cBaI._3PhF6._10YQT._1yL-m', 0)->plaintext;
            $item['timestamp'] = strtotime($article->find('time', 0)->datetime);

            $this->items[] = $item;
        }
    }
}
