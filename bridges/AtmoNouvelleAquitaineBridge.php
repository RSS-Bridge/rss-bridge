<?php

class AtmoNouvelleAquitaineBridge extends BridgeAbstract
{
    const NAME = 'Atmo Nouvelle Aquitaine';
    const URI = 'https://www.atmo-nouvelleaquitaine.org';
    const DESCRIPTION = 'Fetches the latest air polution of cities in Nouvelle Aquitaine from Atmo
    <br><br>To have completion on cities, you must click on the button "Request temporary access to the demo server" 
    <a target="_blank" href="https://cors-anywhere.herokuapp.com/corsdemo">here</a>
    <br><br>Or use your own proxy and change the proxy value in RSS-Bridge config.ini.php';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [[
        'city' => [
            'name' => 'Choisir une ville',
            'type' => 'dynamic_list',
            'ajax_route' => 'https://www.atmo-nouvelleaquitaine.org/sites/nouvelleaquitaine/files/geojsons/communes/communes_500_siam_4.geojson',
            'fields_name_used_as_value_separator' => '/',
            'fields_name_used_as_value' => [
                'properties.nom',
                'properties.code'
            ],
            'fields_name_used_for_display' => [
                'properties.nom',
                'properties.code'
            ],
            'field_for_options' => 'features'
        ]
    ]];
    const CACHE_TIMEOUT = 7200;

    private function getClosest($search, $arr)
    {
        $closest = null;
        foreach ($arr as $key => $value) {
            if ($closest === null || abs((int)$search - $closest) > abs((int)$key - (int)$search)) {
                $closest = (int)$key;
            }
        }
        return $arr[$closest];
    }

    public function collectData()
    {
        $uri = self::URI . '/air-commune/' . $this->getInput('city') . '/indice-atmo';

        $message = $this->getMessageForToday($uri);
        $message .= ' ' . $this->getMessageForTomorrow($uri);

        $item['uri'] = $uri;
        $today = date('d/m/Y');

        $item['title'] = "Bulletin de l'air du $today pour la région Nouvelle Aquitaine.";
        $item['title'] .= ' Retrouvez plus d\'informations en allant sur atmo-nouvelleaquitaine.org #QualiteAir.';
        $item['author'] = 'floviolleau';
        $item['content'] = $message;
        $item['uid'] = hash('sha256', $item['title']);

        $this->items[] = $item;
    }

    private function getMessageForToday(string $uri)
    {
        $html = getSimpleHTMLDOM($uri);

        $message = 'La qualité de l\'air est ' . $html->find('#indice-gauge .c-gauge-title', 0)->innertext . '.';
        $message .= $this->getMessagePolluant($html) . '.';

        return $message;
    }

    private function getMessageForTomorrow(string $uri)
    {
        $tomorrow = (new \DateTime('tomorrow'))->format('Y-m-d');
        $uri = $uri . '?date=' . $tomorrow;

        $html = getSimpleHTMLDOM($uri);

        $message = 'La qualité de l\'air pour demain sera ' . $html->find('#indice-gauge .c-gauge-title', 0)->innertext . '.';
        $message .= $this->getMessagePolluant($html) . '.';

        return $message;
    }

    private function getMessagePolluant(\simple_html_dom $html)
    {
        $message = '';
        foreach ($html->find('.c-indice-content .c-indice-polluant') as $index => $polluant) {
            if ($index === 0) {
                $message .= strip_tags($polluant);
            } else {
                $message .= ';' . strip_tags($polluant);
            }
        }

        return $message;
    }
}
