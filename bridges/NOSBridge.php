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
                    'Laatste nieuws' => 'nieuws/laatste',
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
        $dom = $dom->find('main#content > div > section > ul', 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('li') as $article) {
            $this->items[] = [
                'title' => $article->find('h2', 0)->plaintext,
                'uri' => $article->find('a', 0)->href,
                'content' => $article->find('p', 0)->plaintext,
                'timestamp' => strtotime($article->find('time', 0)->datetime),
            ];
        }
    }
}
