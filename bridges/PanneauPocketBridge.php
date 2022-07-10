<?php

class PanneauPocketBridge extends BridgeAbstract
{
    const NAME = 'Panneau Pocket';
    const URI = 'https://app.panneaupocket.com';
    const DESCRIPTION = 'Fetches the latest infos from Panneau Pocket';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [
        [
            'cities' => [
                'name' => 'Choisir une ville',
                'type' => 'list',
                'values' => self::CITIES,
            ]
        ]
    ];
    const CACHE_TIMEOUT = 7200; // 2h

    private const CITIES = [
        'Andouillé-Neuville-35250' => '1455789521',
        'Aubigné-35250' => '1814317005',
        'Availles-sur-Seiche-35130' => '1892893207',
        'Baulon-35580' => '605833540',
        'Beaucé-35133' => '560906842',
        'Boisgervilly-35360' => '1993806123',
        'Bonnemain-35270' => '1099773691',
        'Bonnemain - Ecole Privée Saint-Joseph-35270' => '538925534',
        'Bonnemain - Ecole Publique Henri Matisse-35270' => '1820283844',
        'Bourg-des-Comptes-35890' => '957084809',
        'Breteil-35160' => '1206807553',
        'Chanteloup-35150' => '65528978',
        'Chavagne-35310' => '1825825704',
        'Cintré-35310' => '857744989',
        'Clayes-35590' => '1176604734',
        'Comblessac-35330' => '799252614',
        'Compagnie de Gendarmerie de Montfort-sur-Meu-35160' => '1310467096',
        'Compagnie de Gendarmerie de Redon-35600' => '772555117',
        'Compagnie de Gendarmerie de Saint-Malo-35400' => '212942271',
        'Compagnie de Gendarmerie de Vitré-35500' => '2117121991',
        'Dingé-35440' => '1146475327',
        'Feins-35440' => '762081007',
        'Gahard-35490' => '858141102',
        'Gendarmerie BTA de Bain-de-Bretagne-35470' => '2125697119',
        'Gendarmerie BTA de Mordelles-35310' => '1915843207',
        'Gendarmerie BTA de Saint-Aubin-du-Cormier-35140' => '1325843950',
        'Gendarmerie BTA de Vitré-35500' => '898672661',
        'Gendarmerie BTA Maen-Roch-35460' => '1096873908',
        'Gendarmerie COB Cancale-35260' => '1992410402',
        'Gendarmerie COB de Chateaugiron-35410' => '1867528169',
        'Gendarmerie COB de Combourg-35270' => '1045617593',
        'Gendarmerie COB de Fougères-35300' => '177248581',
        'Gendarmerie COB de Guichen-35580' => '557627842',
        'Gendarmerie COB de Hédé-Bazouges-35630' => '519881302',
        'Gendarmerie COB de Janzé-35150' => '533620097',
        'Gendarmerie COB de La Guerche-de-Bretagne-35130' => '1282120307',
        'Gendarmerie COB de Montauban de Bretagne-35360' => '137692263',
        'Gendarmerie COB de Redon-35600' => '1027850906',
        'Gendarmerie de Betton-35830' => '307605625',
        'Gosné-35140' => '1261503624',
        'Grand-Fougeray-35390' => '1687416796',
        'Guignen-35580' => '75195882',
        'L\'Hermitage-35590' => '1954292633',
        'La Boussac-35120' => '162444335',
        'La Chapelle-Bouëxic-35330' => '869117325',
        'La Couyère-35320' => '2075958825',
        'La Dominelais-35390' => '2065081911',
        'La Fresnais-35111' => '2010636370',
        'La Gouesnière-35350' => '1925923421',
        'La Noé-Blanche-35470' => '224305391',
        'La Nouaye-35137' => '1000733211',
        'Lalleu-35320' => '1460101917',
        'Landavran-35450' => '133549915',
        'Langouet-35630' => '1523560503',
        'Le Ferré-35420' => '1432943983',
        'Le Verger-35160' => '1266074746',
        'Les Brulais-35330' => '1854147921',
        'Les Portes du Coglais-35460' => '413267621',
        'Livré-sur-Changeon-35450' => '1850101087',
        'Louvigné-de-Bais-35680' => '1676392257',
        'Louvigné-de-Bais - Ecole Charles Perrault-35680' => '1180505145',
        'Louvigné-de-Bais - Ecole Saint-Patern-35680' => '919443746',
        'Maen Roch-35460' => '1112477040',
        'Maison de Quartier Francisco Ferrer-35200' => '944171353',
        'Marcillé-Raoul-35560' => '991970696',
        'Maxent-35380' => '209041860',
        'Meillac-35270' => '1841968856',
        'Mernel-35330' => '1311137811',
        'Monterfil-35160' => '873169651',
        'Montreuil-sur-Ille-35440' => '550764994',
        'Mouazé-35250' => '1931390548',
        'Moutiers-35130' => '443526227',
        'Parigné-35133' => '2013041755',
        'Pleugueneuc-35720' => '748287926',
        'Princé-35210' => '1765498088',
        'Rives-du-Couesnon-35140' => '1609662849',
        'Saint-Aubin-des-Landes-35500' => '1483721395',
        'Saint-Germain-du-Pinel-35370' => '1357547548',
        'Saint-Gonlay-35750' => '711639882',
        'Saint-Péran-35380' => '1484951371',
        'Saint-Séglin-35330' => '292665012',
        'Saint-Thual-35190' => '427165321',
        'Saint-Thurial-35310' => '940529156',
        'Sens-de-Bretagne-35490' => '1055647650',
        'Thourie-35134' => '1250885948',
        'Torcé-35370' => '1927215543',
        'Treffendel-35380' => '738532467',
        'Val d\'Anast-35330' => '225564233',
        'Vallons de Haute Bretagne Communauté-35580' => '1319050928',
        'Vergéal-35680' => '389815752',
        'Vieux-Vy-sur-Couesnon-35490' => '2016313694'
    ];

    public function collectData()
    {
        $found = array_search($this->getInput('cities'), self::CITIES);
        $city = strtolower($this->getInput('cities') . '-' . $found);
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

    /**
     * Produce self::CITIES array
     */
    private static function getCities()
    {
        $cities = json_decode(getContents(self::URI . '/public-api/city'), true);

        $formattedCities = null;
        $citiesString = '[<br>';
        foreach ($cities as $city) {
            if (str_starts_with($city['postCode'], '35')) {
                $formattedCities[$city['name'] . ' - ' . $city['postCode']] = $city['id'];
                $citiesString .= '    "' . $city['name'] . '-' . $city['postCode'] . '" => "' . $city['id'] . '",';
                $citiesString .= '<br>';
            }
        }
        $citiesString .= ']';
        echo '<pre>' . $citiesString . '</pre>';
        die();
    }
}
