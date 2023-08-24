<?php

class NOSBridge extends BridgeAbstract
{
    const NAME = 'NOS Nieuws & Sport Bridge';
    const URI = 'https://www.nos.nl';
    const DESCRIPTION = 'NOS Nieuws & Sport';
    const MAINTAINER = 'wouterkoch';

    const PARAMETERS = [
        [
            'topic' => [
                'type' => 'list',
                'name' => 'Onderwerp',
                'title' => 'Kies onderwerp',
                'values' => [
                    'Laatste nieuws' => 'nieuws',
                    'Binnenland' => 'nieuws/binnenland',
                    'Buitenland' => 'nieuws/buitenland',
                    'Regionaal nieuws' => 'nieuws/regio',
                    'Politiek' => 'nieuws/politiek',
                    'Economie' => 'nieuws/economie',
                    'Koningshuis' => 'nieuws/koningshuis',
                    'Tech' => 'nieuws/tech',
                    'Cultuur en media' => 'nieuws/cultuur-en-media',
                    'Opmerkelijk' => 'nieuws/opmerkelijk',
                    'Voetbal' => 'sport/voetbal',
                    'Formule 1' => 'sport/formule-1',
                    'Wielrennen' => 'sport/wielrennen',
                    'Schaatsen' => 'sport/schaatsen',
                    'Tennis' => 'sport/tennis',
                ],
            ]
        ]
    ];

    public function collectData()
    {
        $url = sprintf('https://www.nos.nl/%s', $this->getInput('topic'));
        $dom = getSimpleHTMLDOM($url);
        $dom = $dom->find('ul.list-items', 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('li.list-items__item') as $article) {
            $a = $article->find('a', 0);
            $this->items[] = [
                'title' => $article->find('h3.list-items__title', 0)->plaintext,
                'uri' => $article->find('a.list-items__link', 0)->href,
                'content' => $article->find('p.list-items__description', 0)->plaintext,
                'timestamp' => strtotime($article->find('time', 0)->datetime),
            ];
        }
    }
}
