<?php

class PanneauPocketBridge extends BridgeAbstract
{
    const NAME = 'Panneau Pocket';
    const URI = 'https://app.panneaupocket.com';
    const DESCRIPTION = 'Fetches the latest infos from Panneau Pocket.
    <br><br>To have completion on cities, you must click on the button "Request temporary access to the demo server" 
    <a target="_blank" href="https://cors-anywhere.herokuapp.com/corsdemo">here</a>
    <br><br>Or use your own proxy and change the proxy value in RSS-Bridge config.ini.php';
    const MAINTAINER = 'floviolleau';
    const CACHE_TIMEOUT = 7200; // 2h
    const PARAMETERS = [[
        'city' => [
            'name' => 'Choisir une ville',
            'type' => 'dynamic_list',
            'ajax_route' => 'https://app.panneaupocket.com/public-api/city',
            'fields_name_used_as_value' => [
                'id',
                'name',
                'postCode'
            ],
            'fields_name_used_for_display' => [
                'name',
                'postCode'
            ],
        ]
    ]];

    public function collectData()
    {
        $city = $this->getInput('city');
        $url = sprintf('https://app.panneaupocket.com/ville/%s', urlencode($city));

        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('.sign-carousel--item') as $itemDom) {
            $item = [];

            $item['uri'] = $itemDom->find('button[type=button]', 0)->href;
            $item['title'] = $itemDom->find('.sign-preview__content .title', 0)->innertext;
            $item['author'] = 'floviolleau';
            $item['content'] = $itemDom->find('.sign-preview__content .content', 0)->innertext;

            $timestamp = $itemDom->find('span.date', 0)->plaintext;
            if (preg_match('#(?<d>[0-9]+)/(?<m>[0-9]+)/(?<y>[0-9]+)#', $timestamp, $match)) {
                $item['timestamp'] = "{$match['y']}-{$match['m']}-{$match['d']}";
            }

            $this->items[] = $item;
        }
    }
}
