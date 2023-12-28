<?php

class SensCritiqueBridge extends BridgeAbstract
{
    const MAINTAINER = 'kranack';
    const NAME = 'Sens Critique';
    const URI = 'https://www.senscritique.com/';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'Sens Critique news';

    const PARAMETERS = [ [
        's' => [
            'name' => 'Series',
            'type' => 'checkbox',
            'defaultValue' => 'checked'
        ],
        'g' => [
            'name' => 'Video Games',
            'type' => 'checkbox'
        ],
        'b' => [
            'name' => 'Books',
            'type' => 'checkbox'
        ],
        'bd' => [
            'name' => 'BD',
            'type' => 'checkbox'
        ],
        'mu' => [
            'name' => 'Music',
            'type' => 'checkbox'
        ]
    ]];

    public function collectData()
    {
        $categories = [];
        foreach (self::PARAMETERS[$this->queriedContext] as $category => $properties) {
            if ($this->getInput($category)) {
                $uri = self::URI;
                switch ($category) {
                    case 's':
                        $uri .= 'series/actualite';
                        break;
                    case 'g':
                        $uri .= 'jeuxvideo/actualite';
                        break;
                    case 'b':
                        $uri .= 'livres/actualite';
                        break;
                    case 'bd':
                        $uri .= 'bd/actualite';
                        break;
                    case 'mu':
                        $uri .= 'musique/actualite';
                        break;
                }
                $html = getSimpleHTMLDOM($uri);
                // This selector name looks like it's automatically generated
                $list = $html->find('div[data-testid="row"]', 0);

                $this->extractDataFromList($list);
            }
        }
    }

    private function extractDataFromList($list)
    {
        if ($list === null) {
            returnClientError('Cannot extract data from list');
        }

        foreach ($list->find('div[data-testid="product-list-item"]') as $movie) {
            $synopsis = $movie->find('p[data-testid="synopsis"]', 0);

            $item = [];
            $item['title'] = $movie->find('h2 a', 0)->plaintext;
            $item['content'] = sprintf(
                '<img src="%s"/><p>%s</p><p>%s</p>%s',
                $movie->find('span[data-testid="poster-img-wrapper"]', 0)->{'data-srcname'},
                $movie->find('p[data-testid="other-infos"]', 0)->innertext,
                $movie->find('p[data-testid="creators"]', 0)->innertext,
                $synopsis ? sprintf('<p>%s</p>', $synopsis->innertext) : ''
            );
            $item['id'] = $this->getURI() . ltrim($movie->find('a', 0)->href, '/');
            $item['uri'] = $this->getURI() . ltrim($movie->find('a', 0)->href, '/');
            $this->items[] = $item;
        }
    }
}
