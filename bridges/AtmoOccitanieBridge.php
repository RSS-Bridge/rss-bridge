<?php

class AtmoOccitanieBridge extends BridgeAbstract
{
    const NAME = 'Atmo Occitanie';
    const URI = 'https://www.atmo-occitanie.org/';
    const DESCRIPTION = 'Fetches the latest air polution of cities in Occitanie from Atmo';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [[
        'city' => [
            'name' => 'Ville',
            'required' => true,
            'exampleValue'  => 'cahors'
        ]
    ]];
    const CACHE_TIMEOUT = 7200;

    public function collectData()
    {
        $uri = self::URI . $this->getInput('city');

        $html = getSimpleHTMLDOM($uri);

        $generalMessage = $html->find('.landing-ville .city-banner .iqa-avertissement', 0)->innertext;
        $recommendationsDom = $html->find('.landing-ville .recommandations', 0);
        $recommendationsItemDom = $recommendationsDom->find('.recommandation-item .label');

        $recommendationsMessage = '';

        $i = 0;
        $len = count($recommendationsItemDom);
        foreach ($recommendationsItemDom as $key => $value) {
            if ($i == 0) {
                $recommendationsMessage .= trim($value->innertext) . '.';
            } else {
                $recommendationsMessage .= ' ' . trim($value->innertext) . '.';
            }
            $i++;
        }

        $lastRecommendationsDom = $recommendationsDom->find('.col-md-6', -1);
        $informationHeaderMessage = $lastRecommendationsDom->find('.heading', 0)->innertext;
        $indice = $lastRecommendationsDom->find('.current-indice .indice div', 0)->innertext;
        $informationDescriptionMessage = $lastRecommendationsDom->find('.current-indice .description p', 0)->innertext;

        $message = "$generalMessage L'indice est de $indice/10. $informationDescriptionMessage. $recommendationsMessage";
        $city = $this->getInput('city');

        $item['uri'] = $uri;
        $today = date('d/m/Y');
        $item['title'] = "Bulletin de l'air du $today pour la ville : $city.";
        //$item['title'] .= ' Retrouvez plus d\'informations en allant sur atmo-occitanie.org #QualiteAir. ' . $message;
        $item['title'] .= ' #QualiteAir. ' . $message;
        $item['author'] = 'floviolleau';
        $item['content'] = $message;
        $item['uid'] = hash('sha256', $item['title']);

        $this->items[] = $item;
    }
}
