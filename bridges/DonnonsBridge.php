<?php

/**
 * Retourne les dons d'une recherche filtrée sur le site Donnons.org
 * Example: https://donnons.org/Sport/Ile-de-France
 */
class DonnonsBridge extends BridgeAbstract
{
    const MAINTAINER = 'Binnette';
    const NAME = 'Donnons.org';
    const URI = 'https://donnons.org';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Retourne les dons depuis le site Donnons.org.';

    const PARAMETERS = [
        [
            'q' => [
                'name' => 'Url de recherche',
                'required' => true,
                'exampleValue' => '/Sport/Ile-de-France',
                'pattern' => '\/.*',
                'title' => 'Faites une recherche sur le site. Puis copiez ici la fin de l’url. Doit commencer par /',
            ],
            'p' => [
                'name' => 'Nombre de pages à scanner',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 5,
                'title' => 'Indique le nombre de pages de donnons.org qui seront scannées'
            ]
        ]
    ];

    public function collectData()
    {
        $pages = $this->getInput('p');

        for ($i = 1; $i <= $pages; $i++) {
            $this->collectDataByPage($i);
        }
    }

    private function collectDataByPage($page)
    {
        $uri = $this->getPageURI($page);

        $html = getSimpleHTMLDOM($uri);

        $searchDiv = $html->find('div[id=search]', 0);

        if (!is_null($searchDiv)) {
            $elements = $searchDiv->find('a.lst-annonce');
            foreach ($elements as $element) {
                $item = [];

                // Lien vers le don
                $item['uri'] = self::URI . $element->href;
                // Id de l'objet
                $item['uid'] = $element->getAttribute('data-id');

                // Grab info from json
                $jsonString = $element->find('script', 0)->innertext;
                $json = json_decode($jsonString, true);

                $name = $json['name'];
                $category = $json['category'];
                $date = $json['availabilityStarts'];
                $description = $json['description'];
                $city = $json['availableAtOrFrom']['address']['addressLocality'];
                $region = $json['availableAtOrFrom']['address']['addressRegion'];

                // Grab info from HTML
                $imageSrc = $element->find('img.ima-center', 0)->getAttribute('src');
                // Use large image instead of small one
                $imageSrc = str_replace('/xs/', '/lg/', $imageSrc);
                $image = self::URI . $imageSrc;
                $author = $element->find('div.avatar-holder', 0)->plaintext;

                $content = '
					<img style="margin-right:1em;" src="' . $image . '">
					<div>
						<h1>' . $name . '</h1>
						<p>' . $description . '</p>
						<p>Lieu : <b>' . $city . '</b> - ' . $region . '</p>
						<p>Par : ' . $author . '</p>
						<p>Date : ' . $date . '</p>
					</div>
				';

                // Titre du don
                $item['title'] = '[' . $category . '] ' . $name;
                $item['timestamp'] = $date;
                $item['author'] = $author;
                $item['content'] = $content;
                $item['enclosures'] = [$image];

                $this->items[] = $item;
            }
        }
    }

    private function getPageURI($page)
    {
        $uri = $this->getURI();
        $haveQueryParams = strpos($uri, '?') !== false;

        if ($haveQueryParams) {
            return $uri . '&page=' . $page;
        } else {
            return $uri . '?page=' . $page;
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('q'))) {
            return self::URI . $this->getInput('q');
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('q'))) {
            return 'Donnons.org - ' . $this->getInput('q');
        }

        return parent::getName();
    }
}
