<?php

class AutoJMBridge extends BridgeAbstract
{
    const NAME = 'AutoJM';
    const URI = 'https://www.autojm.fr/';
    const DESCRIPTION = 'Suivre les offres de véhicules proposés par AutoJM en fonction des critères de filtrages';
    const MAINTAINER = 'sysadminstory';
    const PARAMETERS = [
        'Afficher les offres de véhicules disponible sur la recheche AutoJM' => [
            'url' => [
                'name' => 'URL de la page de recherche',
                'type' => 'text',
                'required' => true,
                'title' => 'URL d\'une recherche avec filtre de véhicules sans le http://www.autojm.fr/',
                'exampleValue' => 'recherche?brands[]=PEUGEOT&ranges[]=PEUGEOT 308'
            ],
        ]
    ];

    const CACHE_TIMEOUT = 3600;

    const TEST_DETECT_PARAMETERS = [
        'https://www.autojm.fr/recherche?brands%5B%5D=PEUGEOT&ranges%5B%5D=PEUGEOT%20308'
            => ['url' => 'recherche?brands%5B%5D=PEUGEOT&ranges%5B%5D=PEUGEOT%20308',
                'context' => 'Afficher les offres de véhicules disponible sur la recheche AutoJM'
            ]
    ];

    public function getIcon()
    {
        return self::URI . 'favicon.ico';
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Afficher les offres de véhicules disponible sur la recheche AutoJM':
                return 'AutoJM | Recherche de véhicules';
            break;
            default:
                return parent::getName();
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Afficher les offres de véhicules disponible sur la recheche AutoJM':
                return self::URI . $this->getInput('url');
            break;
            default:
                return self::URI;
        }
    }

    public function collectData()
    {
        // Get the number of result for this search
        $search_url = self::URI . $this->getInput('url') . '&open=energy&onlyFilters=false';

        // Set the header 'X-Requested-With' like the website does it
        $header = [
            'X-Requested-With: XMLHttpRequest'
        ];

        // Get the JSON content of the form
        $json = getContents($search_url, $header);

        // Extract the HTML content from the JSON result
        $data = json_decode($json);

        $nb_results = $data->nbResults;
        $total_pages = ceil($nb_results / 14);

        // Limit the number of page to analyse to 10
        for ($page = 1; $page <= $total_pages && $page <= 10; $page++) {
            // Get the result the next page
            $html = $this->getResults($page);

            // Go through every car of the search
            $list = $html->find('div[class*=card-car card-car--listing]');
            foreach ($list as $car) {
                // Get the info about the car offer
                $image = $car->find('div[class=card-car__header__img]', 0)->find('img', 0)->src;
                // Decode HTML attribute JSON data
                $car_data = json_decode(html_entity_decode($car->{'data-layer'}));
                $car_model = $car_data->title;
                $availability = $car->find('div[class*=card-car__modalites]', 0)->find('div[class=col]', 0)->plaintext;
                $warranty = $car->find('div[data-type=WarrantyCard]', 0)->plaintext;
                $discount_html = $car->find('div[class=subtext vehicle_reference_element]', 0);
                // Check if there is any discount info displayed
                if ($discount_html != null) {
                    $reference_price_value = $discount_html->find('span[data-cfg=vehicle__reference_price]', 0)->plaintext;
                    $discount_percent_value = $discount_html->find('span[data-cfg=vehicle__discount_percent]', 0)->plaintext;
                    $reference_price = '<li>Prix de référence : <s>' . $reference_price_value . '</s></li>';
                    $discount_percent = '<li>Réduction : ' . $discount_percent_value . ' %</li>';
                } else {
                    $reference_price = '';
                    $discount_percent = '';
                }
                $price = $car_data->price;
                $kilometer = $car->find('span[data-cfg=vehicle__kilometer]', 0)->plaintext;
                $energy = $car->find('span[data-cfg=vehicle__energy__label]', 0)->plaintext;
                $power = $car->find('span[data-cfg=vehicle__tax_horse_power]', 0)->plaintext;
                $seats = $car->find('span[data-cfg=vehicle__seats]', 0)->plaintext;
                $doors = $car->find('span[data-cfg=vehicle__door__label]', 0)->plaintext;
                $transmission = $car->find('span[data-cfg=vehicle__transmission]', 0)->plaintext;
                $loa_html = $car->find('span[data-cfg=vehicle__loa]', 0);
                // Check if any LOA price is displayed
                if ($loa_html != null) {
                    $loa_value = $car->find('span[data-cfg=vehicle__loa]', 0)->plaintext;
                    $loa = '<li>LOA : à partir de ' . $loa_value . ' / mois </li>';
                } else {
                    $loa = '';
                }

                // Construct the new item
                $item = [];
                $item['title'] = $car_model;
                $item['content'] = '<p><img style="vertical-align:middle ; padding: 10px" src="' . $image . '" />'
                    . $car_model . '</p>';
                $item['content'] .= '<ul><li>Disponibilité : ' . $availability . '</li>';
                $item['content'] .= '<li>Prix : ' . $price . ' €</li>';
                $item['content'] .= $reference_price;
                $item['content'] .= $loa;
                $item['content'] .= $discount_percent;
                $item['content'] .= '<li>Garantie : ' . $warranty . '</li>';
                $item['content'] .= '<li>Kilométrage : ' . $kilometer . ' km</li>';
                $item['content'] .= '<li>Energie : ' . $energy . '</li>';
                $item['content'] .= '<li>Puissance: ' . $power . ' CV Fiscaux</li>';
                $item['content'] .= '<li>Nombre de Places : ' . $seats . ' place(s)</li>';
                $item['content'] .= '<li>Nombre de portes : ' . $doors . '</li>';
                $item['content'] .= '<li>Boite de vitesse : ' . $transmission . '</li></ul>';
                $item['uri'] = $car_data->{'uri'};
                $item['uid'] = hash('md5', $item['content']);
                $this->items[] = $item;
            }
        }
    }

    private function getResults(int $page)
    {
        $user_input = $this->getInput('url');
        $search_data = preg_replace('#(recherche|recherche/[0-9]{1,10})\?#', 'recherche/' . $page . '?', $user_input);

        $search_url = self::URI . $search_data . '&open=energy&onlyFilters=false';

        // Get the HTML content of the page
        $html = getSimpleHTMLDOMCached($search_url);

        return $html;
    }

    public function detectParameters($url)
    {
        $params = [];
        $regex = '/^(https?:\/\/)?(www\.|)autojm.fr\/(recherche\?.*|recherche\/[0-9]{1,10}\?.*)$/m';
        if (preg_match($regex, $url, $matches) > 0) {
            $url = preg_replace('#(recherche|recherche/[0-9]{1,10})#', 'recherche', $matches[3]);

            $params['url'] = $url;
            $params['context'] = 'Afficher les offres de véhicules disponible sur la recheche AutoJM';

            return $params;
        }
    }
}
