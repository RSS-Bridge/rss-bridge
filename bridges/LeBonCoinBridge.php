<?php

class LeBonCoinBridge extends BridgeAbstract
{
    const MAINTAINER = 'jacknumber';
    const NAME = 'LeBonCoin';
    const URI = 'https://www.leboncoin.fr/';
    const DESCRIPTION = 'Returns most recent results from LeBonCoin';

    const PARAMETERS = [
        [
            'keywords' => ['name' => 'Mots-Clés'],
            'region' => [
                'name' => 'Région',
                'type' => 'list',
                'values' => [
                    'Toute la France' => '',
                    'Alsace' => '1',
                    'Aquitaine' => '2',
                    'Auvergne' => '3',
                    'Basse Normandie' => '4',
                    'Bourgogne' => '5',
                    'Bretagne' => '6',
                    'Centre' => '7',
                    'Champagne Ardenne' => '8',
                    'Corse' => '9',
                    'Franche Comté' => '10',
                    'Haute Normandie' => '11',
                    'Ile de France' => '12',
                    'Languedoc Roussillon' => '13',
                    'Limousin' => '14',
                    'Lorraine' => '15',
                    'Midi Pyrénées' => '16',
                    'Nord Pas De Calais' => '17',
                    'Pays de la Loire' => '18',
                    'Picardie' => '19',
                    'Poitou Charentes' => '20',
                    'Provence Alpes Côte d\'Azur' => '21',
                    'Rhône-Alpes' => '22',
                    'Guadeloupe' => '23',
                    'Martinique' => '24',
                    'Guyane' => '25',
                    'Réunion' => '26'
                ]
            ],
            'department' => [
                'name' => 'Département',
                'type' => 'list',
                'values' => [
                    '' => '',
                    'Ain' => '1',
                    'Aisne' => '2',
                    'Allier' => '3',
                    'Alpes-de-Haute-Provence' => '4',
                    'Hautes-Alpes' => '5',
                    'Alpes-Maritimes' => '6',
                    'Ardèche' => '7',
                    'Ardennes' => '8',
                    'Ariège' => '9',
                    'Aube' => '10',
                    'Aude' => '11',
                    'Aveyron' => '12',
                    'Bouches-du-Rhône' => '13',
                    'Calvados' => '14',
                    'Cantal' => '15',
                    'Charente' => '16',
                    'Charente-Maritime' => '17',
                    'Cher' => '18',
                    'Corrèze' => '19',
                    'Corse-du-Sud' => '2A',
                    'Haute-Corse' => '2B',
                    'Côte-d\'Or' => '21',
                    'Côtes-d\'Armor' => '22',
                    'Creuse' => '23',
                    'Dordogne' => '24',
                    'Doubs' => '25',
                    'Drôme' => '26',
                    'Eure' => '27',
                    'Eure-et-Loir' => '28',
                    'Finistère' => '29',
                    'Gard' => '30',
                    'Haute-Garonne' => '31',
                    'Gers' => '32',
                    'Gironde' => '33',
                    'Hérault' => '34',
                    'Ille-et-Vilaine' => '35',
                    'Indre' => '36',
                    'Indre-et-Loire' => '37',
                    'Isère' => '38',
                    'Jura' => '39',
                    'Landes' => '40',
                    'Loir-et-Cher' => '41',
                    'Loire' => '42',
                    'Haute-Loire' => '43',
                    'Loire-Atlantique' => '44',
                    'Loiret' => '45',
                    'Lot' => '46',
                    'Lot-et-Garonne' => '47',
                    'Lozère' => '48',
                    'Maine-et-Loire' => '49',
                    'Manche' => '50',
                    'Marne' => '51',
                    'Haute-Marne' => '52',
                    'Mayenne' => '53',
                    'Meurthe-et-Moselle' => '54',
                    'Meuse' => '55',
                    'Morbihan' => '56',
                    'Moselle' => '57',
                    'Nièvre' => '58',
                    'Nord' => '59',
                    'Oise' => '60',
                    'Orne' => '61',
                    'Pas-de-Calais' => '62',
                    'Puy-de-Dôme' => '63',
                    'Pyrénées-Atlantiques' => '64',
                    'Hautes-Pyrénées' => '65',
                    'Pyrénées-Orientales' => '66',
                    'Bas-Rhin' => '67',
                    'Haut-Rhin' => '68',
                    'Rhône' => '69',
                    'Haute-Saône' => '70',
                    'Saône-et-Loire' => '71',
                    'Sarthe' => '72',
                    'Savoie' => '73',
                    'Haute-Savoie' => '74',
                    'Paris' => '75',
                    'Seine-Maritime' => '76',
                    'Seine-et-Marne' => '77',
                    'Yvelines' => '78',
                    'Deux-Sèvres' => '79',
                    'Somme' => '80',
                    'Tarn' => '81',
                    'Tarn-et-Garonne' => '82',
                    'Var' => '83',
                    'Vaucluse' => '84',
                    'Vendée' => '85',
                    'Vienne' => '86',
                    'Haute-Vienne' => '87',
                    'Vosges' => '88',
                    'Yonne' => '89',
                    'Territoire de Belfort' => '90',
                    'Essonne' => '91',
                    'Hauts-de-Seine' => '92',
                    'Seine-Saint-Denis' => '93',
                    'Val-de-Marne' => '94',
                    'Val-d\'Oise' => '95'
                ]
            ],
            'cities' => [
                'name' => 'Villes',
                'title' => 'Codes postaux séparés par des virgules'
            ],
            'category' => [
                'name' => 'Catégorie',
                'type' => 'list',
                'values' => [
                    'Toutes catégories' => '',
                    'EMPLOI' => [
                        'Emploi et recrutement' => '71',
                        'Offres d\'emploi et jobs' => '33'
                    ],
                    'VÉHICULES' => [
                        'Tous' => '1',
                        'Voitures' => '2',
                        'Motos' => '3',
                        'Caravaning' => '4',
                        'Utilitaires' => '5',
                        'Equipement Auto' => '6',
                        'Equipement Moto' => '44',
                        'Equipement Caravaning' => '50',
                        'Nautisme' => '7',
                        'Equipement Nautisme' => '51'
                    ],
                    'IMMOBILIER' => [
                        'Tous' => '8',
                        'Ventes immobilières' => '9',
                        'Locations' => '10',
                        'Colocations' => '11',
                        'Bureaux & Commerces' => '13'
                    ],
                    'VACANCES' => [
                        'Tous' => '66',
                        'Locations & Gîtes' => '12',
                        'Chambres d\'hôtes' => '67',
                        'Campings' => '68',
                        'Hôtels' => '69',
                        'Hébergements insolites' => '70'
                    ],
                    'MULTIMÉDIA' => [
                        'Tous' => '14',
                        'Informatique' => '15',
                        'Consoles & Jeux vidéo' => '43',
                        'Image & Son' => '16',
                        'Téléphonie' => '17'
                    ],
                    'LOISIRS' => [
                        'Tous' => '24',
                        'DVD / Films' => '25',
                        'CD / Musique' => '26',
                        'Livres' => '27',
                        'Animaux' => '28',
                        'Vélos' => '55',
                        'Sports & Hobbies' => '29',
                        'Instruments de musique' => '30',
                        'Collection' => '40',
                        'Jeux & Jouets' => '41',
                        'Vins & Gastronomie' => '48'
                    ],
                    'MATÉRIEL PROFESSIONNEL' => [
                        'Tous' => '56',
                        'Matériel Agricole' => '57',
                        'Transport - Manutention' => '58',
                        'BTP - Chantier Gros-oeuvre' => '59',
                        'Outillage - Matériaux 2nd-oeuvre' => '60',
                        'Équipements Industriels' => '32',
                        'Restauration - Hôtellerie' => '61',
                        'Fournitures de Bureau' => '62',
                        'Commerces & Marchés' => '63',
                        'Matériel Médical' => '64'
                    ],
                    'SERVICES' => [
                        'Tous' => '31',
                        'Prestations de services' => '34',
                        'Billetterie' => '35',
                        'Événements' => '49',
                        'Cours particuliers' => '36',
                        'Covoiturage' => '65'
                    ],
                    'MAISON' => [
                        'Tous' => '18',
                        'Ameublement' => '19',
                        'Électroménager' => '20',
                        'Arts de la table' => '45',
                        'Décoration' => '39',
                        'Linge de maison' => '46',
                        'Bricolage' => '21',
                        'Jardinage' => '52',
                        'Vêtements' => '22',
                        'Chaussures' => '53',
                        'Accessoires & Bagagerie' => '47',
                        'Montres & Bijoux' => '42',
                        'Équipement bébé' => '23',
                        'Vêtements bébé' => '54',
                    ],
                    'AUTRES' => '37'
                ]
            ],
            'pricemin' => [
                'name' => 'Prix min',
                'type' => 'number'
            ],
            'pricemax' => [
                'name' => 'Prix max',
                'type' => 'number'
            ],
            'estate' => [
                'name' => 'Type de bien',
                'type' => 'list',
                'values' => [
                    '' => '',
                    'Maison' => '1',
                    'Appartement' => '2',
                    'Terrain' => '3',
                    'Parking' => '4',
                    'Autre' => '5'
                ]
            ],
            'roomsmin' => [
                'name' => 'Pièces min',
                'type' => 'number'
            ],
            'roomsmax' => [
                'name' => 'Pièces max',
                'type' => 'number'
            ],
            'squaremin' => [
                'name' => 'Surface min',
                'type' => 'number'
            ],
            'squaremax' => [
                'name' => 'Surface max',
                'type' => 'number'
            ],
            'mileagemin' => [
                'name' => 'Kilométrage min',
                'type' => 'number'
            ],
            'mileagemax' => [
                'name' => 'Kilométrage max',
                'type' => 'number'
            ],
            'yearmin' => [
                'name' => 'Année min',
                'type' => 'number'
            ],
            'yearmax' => [
                'name' => 'Année max',
                'type' => 'number'
            ],
            'cubiccapacitymin' => [
                'name' => 'Cylindrée min',
                'type' => 'number'
            ],
            'cubiccapacitymax' => [
                'name' => 'Cylindrée max',
                'type' => 'number'
            ],
            'fuel' => [
                'name' => 'Énergie',
                'type' => 'list',
                'values' => [
                    '' => '',
                    'Essence' => '1',
                    'Diesel' => '2',
                    'GPL' => '3',
                    'Électrique' => '4',
                    'Hybride' => '6',
                    'Autre' => '5'
                ]
            ],
            'owner' => [
                'name' => 'Vendeur',
                'type' => 'list',
                'values' => [
                    'Tous' => '',
                    'Particuliers' => 'private',
                    'Professionnels' => 'pro'
                ]
            ]
        ]
    ];

    public static $LBC_API_KEY = 'ba0c2dad52b3ec';

    private function getRange($field, $range_min, $range_max)
    {
        if (
            !is_null($range_min)
            && !is_null($range_max)
            && $range_min > $range_max
        ) {
            throwClientException('Min-' . $field . ' must be lower than max-' . $field . '.');
        }

        if (
            !is_null($range_min)
            && is_null($range_max)
        ) {
            throwClientException('Max-' . $field . ' is needed when min-' . $field . ' is setted (range).');
        }

        return [
            'min' => $range_min,
            'max' => $range_max
        ];
    }

    public function collectData()
    {
        $url = 'https://api.leboncoin.fr/api/adfinder/v1/search';
        $data = $this->buildRequestJson();

        $header = [
            'User-Agent: LBC;Android;10;SAMSUNG;phone;0aaaaaaaaaaaaaaa;wifi;8.24.3.8;152437;0',
            'Content-Type: application/json',
            'X-LBC-CC: 7',
            'Accept: application/json,application/hal+json',
            'Content-Length: ' . strlen($data),
            'api_key: ' . self::$LBC_API_KEY
        ];

        $opts = [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data

        ];

        $content = getContents($url, $header, $opts);

        $json = json_decode($content);

        if ($json->total === 0) {
            return;
        }

        foreach ($json->ads as $element) {
            $item['title'] = $element->subject;
            $item['content'] = $element->body;
            $item['date'] = $element->index_date;
            $item['timestamp'] = strtotime($element->index_date);
            $item['uri'] = $element->url;
            $item['ad_type'] = $element->ad_type;
            $item['author'] = $element->owner->name;

            if (isset($element->location->city)) {
                $item['city'] = $element->location->city;
                $item['content'] .= ' -- ' . $element->location->city;
            }

            if (isset($element->location->zipcode)) {
                $item['zipcode'] = $element->location->zipcode;
            }

            if (isset($element->price)) {
                $item['price'] = $element->price[0];
                $item['content'] .= ' -- ' . current($element->price) . '€';
            }

            if (isset($element->images->urls)) {
                $item['thumbnail'] = $element->images->thumb_url;
                $item['enclosures'] = [];

                foreach ($element->images->urls as $image) {
                    $item['enclosures'][] = $image;
                }
            }

            $this->items[] = $item;
        }
    }

    private function buildRequestJson()
    {
        $requestJson = new StdClass();
        $requestJson->owner_type = $this->getInput('owner');
        $requestJson->filters = new StdClass();

        $requestJson->filters->keywords = [
            'text' => $this->getInput('keywords')
        ];

        if ($this->getInput('region') != '') {
            $requestJson->filters->location['regions'] = [$this->getInput('region')];
        }

        if ($this->getInput('department') != '') {
            $requestJson->filters->location['departments'] = [$this->getInput('department')];
        }

        if ($this->getInput('cities') != '') {
            $requestJson->filters->location['city_zipcodes'] = [];

            foreach (explode(',', $this->getInput('cities')) as $zipcode) {
                $requestJson->filters->location['city_zipcodes'][] = [
                    'zipcode' => trim($zipcode)
                ];
            }
        }

        $requestJson->filters->category = [
            'id' => $this->getInput('category')
        ];

        if (
            $this->getInput('pricemin') != ''
            || $this->getInput('pricemax') != ''
        ) {
            $requestJson->filters->ranges->price = $this->getRange(
                'price',
                $this->getInput('pricemin'),
                $this->getInput('pricemax')
            );
        }

        if ($this->getInput('estate') != '') {
            $requestJson->filters->enums['real_estate_type'] = [$this->getInput('estate')];
        }

        if (
            $this->getInput('roomsmin') != ''
            || $this->getInput('roomsmax') != ''
        ) {
            $requestJson->filters->ranges->rooms = $this->getRange(
                'rooms',
                $this->getInput('roomsmin'),
                $this->getInput('roomsmax')
            );
        }

        if (
            $this->getInput('squaremin') != ''
            || $this->getInput('squaremax') != ''
        ) {
            $requestJson->filters->ranges->square = $this->getRange(
                'square',
                $this->getInput('squaremin'),
                $this->getInput('squaremax')
            );
        }

        if (
            $this->getInput('mileagemin') != ''
            || $this->getInput('mileagemax') != ''
        ) {
            $requestJson->filters->ranges->mileage = $this->getRange(
                'mileage',
                $this->getInput('mileagemin'),
                $this->getInput('mileagemax')
            );
        }

        if (
            $this->getInput('yearmin') != ''
            || $this->getInput('yearmax') != ''
        ) {
            $requestJson->filters->ranges->regdate = $this->getRange(
                'year',
                $this->getInput('yearmin'),
                $this->getInput('yearmax')
            );
        }

        if (
            $this->getInput('cubiccapacitymin') != ''
            || $this->getInput('cubiccapacitymax') != ''
        ) {
            $requestJson->filters->ranges->cubic_capacity = $this->getRange(
                'cubic_capacity',
                $this->getInput('cubiccapacitymin'),
                $this->getInput('cubiccapacitymax')
            );
        }

        if ($this->getInput('fuel') != '') {
            $requestJson->filters->enums['fuel'] = [$this->getInput('fuel')];
        }

        $requestJson->limit = 30;

        return json_encode($requestJson);
    }
}
