<?php

class NautiljonBridge extends BridgeAbstract
{
    const NAME = 'Nautiljon Bridge';
    const URI = 'https://www.nautiljon.com';
    const DESCRIPTION = 'Actualités et Brèves de Nautiljon.';
    const MAINTAINER = 'papjul';

    const PARAMETERS = [
        [
            'type' => [
                'type' => 'list',
                'name' => 'Type',
                'title' => 'Choisir le type',
                'values' => [
                    'Actualités' => 'actualite',
                    'Brèves' => 'breves',
                ],
            ]
        ]
    ];

    private function formatDate($fright)
    {
        preg_match('#^(.*)</a>(.*)<a(.*)$#', $fright, $matches);
        if ($matches) {
            $frenchFormat = trim($matches[2]);
            $englishFormat = str_replace(['aujourd\'hui', 'hier', 'à', 'le', '-'], ['today', 'yesterday', '', '', ''], $frenchFormat);
            $englishFormat = preg_replace('#([0-9]{2})/([0-9]{2})/([0-9]{4})#', '$2/$1/$3', $englishFormat);
            return strtotime($englishFormat);
        } else {
            return null;
        }
    }

    public function collectData()
    {
        $url = sprintf('https://www.nautiljon.com/%s/', $this->getInput('type'));
        $dom = getSimpleHTMLDOM($url);

        foreach ($dom->find('div.une_actu') as $article) {
            $fright = $article->find('span.fright', 0);
            $this->items[] = [
                'title' => $article->find('h3 a', 0)->plaintext,
                'uri' => self::URI . $article->find('h3 a', 0)->href,
                'content' => $article->find('p', 0)->plaintext,
                'author' => $fright->find('a', 0)->plaintext,
                'categories' => [($fright->find('a')[1])->plaintext],
                'enclosures' => [self::URI . $article->find('a img', 0)->src],
                'timestamp' => $this->formatDate($article->find('span.fright', 0)),
            ];
        }
    }
}
