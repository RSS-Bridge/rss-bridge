<?php

class LaCentraleBridge extends BridgeAbstract
{
    const MAINTAINER = 'jacknumber';
    const NAME = 'La Centrale';
    const URI = 'https://www.lacentrale.fr/';
    const DESCRIPTION = 'Returns most recent vehicules ads from LaCentrale';

    const PARAMETERS = [ [
        'type' => [
            'name' => 'Type de véhicule',
            'type' => 'list',
            'values' => [
                'Voiture' => 'car',
                'Camion/Pickup' => 'truck',
                'Moto' => 'moto',
                'Scooter' => 'scooter',
                'Quad' => 'quad',
                'Caravane/Camping-car' => 'mobileHome'
            ]
        ],
        'brand' => [
            'name' => 'Marque',
            'type' => 'list',
            'values' => [
                '' => '',
                'ABARTH' => 'ABARTH',
                'AC' => 'AC',
                'AIXAM' => 'AIXAM',
                'ALFA ROMEO' => 'ALFA ROMEO',
                'ALKE' => 'ALKE',
                'ALPINA' => 'ALPINA',
                'ALPINE' => 'ALPINE',
                'AMC' => 'AMC',
                'ANAIG' => 'ANAIG',
                'APRILIA' => 'APRILIA',
                'ARIEL' => 'ARIEL',
                'ASTON MARTIN' => 'ASTON MARTIN',
                'AUDI' => 'AUDI',
                'AUSTIN HEALEY' => 'AUSTIN HEALEY',
                'AUSTIN' => 'AUSTIN',
                'AUTOBIANCHI' => 'AUTOBIANCHI',
                'AVINTON' => 'AVINTON',
                'BELLIER' => 'BELLIER',
                'BENELLI' => 'BENELLI',
                'BENTLEY' => 'BENTLEY',
                'BETA' => 'BETA',
                'BMW' => 'BMW',
                'BOLLORE' => 'BOLLORE',
                'BRIXTON' => 'BRIXTON',
                'BUELL' => 'BUELL',
                'BUGATTI' => 'BUGATTI',
                'BUICK' => 'BUICK',
                'BULLIT' => 'BULLIT',
                'CADILLAC' => 'CADILLAC',
                'CASALINI' => 'CASALINI',
                'CATERHAM' => 'CATERHAM',
                'CHATENET' => 'CHATENET',
                'CHEVROLET' => 'CHEVROLET',
                'CHRYSLER' => 'CHRYSLER',
                'CHUNLAN' => 'CHUNLAN',
                'CITROEN' => 'CITROEN',
                'COURB' => 'COURB',
                'CR&S' => 'CR&S',
                'CUPRA' => 'CUPRA',
                'CYCLONE' => 'CYCLONE',
                'DACIA' => 'DACIA',
                'DAELIM' => 'DAELIM',
                'DAEWOO' => 'DAEWOO',
                'DAF' => 'DAF',
                'DAIHATSU' => 'DAIHATSU',
                'DANGEL' => 'DANGEL',
                'DATSUN' => 'DATSUN',
                'DE SOTO' => 'DE SOTO',
                'DE TOMASO' => 'DE TOMASO',
                'DERBI' => 'DERBI',
                'DEVINCI' => 'DEVINCI',
                'DODGE' => 'DODGE',
                'DONKERVOORT' => 'DONKERVOORT',
                'DS' => 'DS',
                'DUCATI' => 'DUCATI',
                'DUCATY' => 'DUCATY',
                'DUE' => 'DUE',
                'ENFIELD' => 'ENFIELD',
                'EXCALIBUR' => 'EXCALIBUR',
                'FACEL VEGA' => 'FACEL VEGA',
                'FANTIC MOTOR' => 'FANTIC MOTOR',
                'FERRARI' => 'FERRARI',
                'FIAT' => 'FIAT',
                'FISKER' => 'FISKER',
                'FORD' => 'FORD',
                'FUSO' => 'FUSO',
                'GAS GAS' => 'GAS GAS',
                'GILERA' => 'GILERA',
                'GMC' => 'GMC',
                'GOWINN' => 'GOWINN',
                'GRANDIN' => 'GRANDIN',
                'HARLEY DAVIDSON' => 'HARLEY DAVIDSON',
                'HOMMELL' => 'HOMMELL',
                'HONDA' => 'HONDA',
                'HUMMER' => 'HUMMER',
                'HUSABERG' => 'HUSABERG',
                'HUSQVARNA' => 'HUSQVARNA',
                'HYOSUNG' => 'HYOSUNG',
                'HYUNDAI' => 'HYUNDAI',
                'INDIAN' => 'INDIAN',
                'INFINITI' => 'INFINITI',
                'INNOCENTI' => 'INNOCENTI',
                'ISUZU' => 'ISUZU',
                'IVECO' => 'IVECO',
                'JAGUAR' => 'JAGUAR',
                'JDM SIMPA' => 'JDM SIMPA',
                'JEEP' => 'JEEP',
                'JENSEN' => 'JENSEN',
                'JIAYUAN' => 'JIAYUAN',
                'KAWASAKI' => 'KAWASAKI',
                'KEEWAY' => 'KEEWAY',
                'KIA' => 'KIA',
                'KSR' => 'KSR',
                'KTM' => 'KTM',
                'KYMCO' => 'KYMCO',
                'LADA' => 'LADA',
                'LAMBORGHINI' => 'LAMBORGHINI',
                'LANCIA' => 'LANCIA',
                'LAND ROVER' => 'LAND ROVER',
                'LEXUS' => 'LEXUS',
                'LIGIER' => 'LIGIER',
                'LINCOLN' => 'LINCOLN',
                'LONDON TAXI COMPANY' => 'LONDON TAXI COMPANY',
                'LOTUS' => 'LOTUS',
                'MAGPOWER' => 'MAGPOWER',
                'MAN' => 'MAN',
                'MASAI' => 'MASAI',
                'MASERATI' => 'MASERATI',
                'MASH' => 'MASH',
                'MATRA' => 'MATRA',
                'MAYBACH' => 'MAYBACH',
                'MAZDA' => 'MAZDA',
                'MCLAREN' => 'MCLAREN',
                'MEGA' => 'MEGA',
                'MERCEDES' => 'MERCEDES',
                'MERCEDES-AMG' => 'MERCEDES-AMG',
                'MERCURY' => 'MERCURY',
                'MEYERS MANX' => 'MEYERS MANX',
                'MG' => 'MG',
                'MIA ELECTRIC' => 'MIA ELECTRIC',
                'MICROCAR' => 'MICROCAR',
                'MINAUTO' => 'MINAUTO',
                'MINI' => 'MINI',
                'MITSUBISHI' => 'MITSUBISHI',
                'MORGAN' => 'MORGAN',
                'MORRIS' => 'MORRIS',
                'MOTO GUZZI' => 'MOTO GUZZI',
                'MOTO MORINI' => 'MOTO MORINI',
                'MOTOBECANE' => 'MOTOBECANE',
                'MPM MOTORS' => 'MPM MOTORS',
                'MV AGUSTA' => 'MV AGUSTA',
                'NISSAN' => 'NISSAN',
                'NORTON' => 'NORTON',
                'NSU' => 'NSU',
                'OLDSMOBILE' => 'OLDSMOBILE',
                'OPEL' => 'OPEL',
                'ORCAL' => 'ORCAL',
                'OSSA' => 'OSSA',
                'PACKARD' => 'PACKARD',
                'PANTHER' => 'PANTHER',
                'PEUGEOT' => 'PEUGEOT',
                'PGO' => 'PGO',
                'PIAGGIO' => 'PIAGGIO',
                'PLYMOUTH' => 'PLYMOUTH',
                'POLARIS' => 'POLARIS',
                'PONTIAC' => 'PONTIAC',
                'PORSCHE' => 'PORSCHE',
                'REALM' => 'REALM',
                'REGAL RAPTOR' => 'REGAL RAPTOR',
                'RENAULT' => 'RENAULT',
                'RIEJU' => 'RIEJU',
                'ROLLS ROYCE' => 'ROLLS ROYCE',
                'ROVER' => 'ROVER',
                'ROYAL ENFIELD' => 'ROYAL ENFIELD',
                'SAAB' => 'SAAB',
                'SANTANA' => 'SANTANA',
                'SCANIA' => 'SCANIA',
                'SEAT' => 'SEAT',
                'SECMA' => 'SECMA',
                'SHELBY' => 'SHELBY',
                'SHERCO' => 'SHERCO',
                'SIMCA' => 'SIMCA',
                'SKODA' => 'SKODA',
                'SMART' => 'SMART',
                'SPYKER' => 'SPYKER',
                'SSANGYONG' => 'SSANGYONG',
                'STUDEBAKER' => 'STUDEBAKER',
                'SUBARU' => 'SUBARU',
                'SUNBEAM' => 'SUNBEAM',
                'SUZUKI' => 'SUZUKI',
                'SWM' => 'SWM',
                'SYM' => 'SYM',
                'TALBOT SIMCA' => 'TALBOT SIMCA',
                'TALBOT' => 'TALBOT',
                'TEILHOL' => 'TEILHOL',
                'TESLA' => 'TESLA',
                'TM' => 'TM',
                'TNT MOTOR' => 'TNT MOTOR',
                'TOYOTA' => 'TOYOTA',
                'TRIUMPH' => 'TRIUMPH',
                'TVR' => 'TVR',
                'VAUXHALL' => 'VAUXHALL',
                'VESPA' => 'VESPA',
                'VICTORY' => 'VICTORY',
                'VOLKSWAGEN' => 'VOLKSWAGEN',
                'VOLVO' => 'VOLVO',
                'VOXAN' => 'VOXAN',
                'WIESMANN' => 'WIESMANN',
                'YAMAHA' => 'YAMAHA',
                'YCF' => 'YCF',
                'ZERO' => 'ZERO',
                'ZONGSHEN' => 'ZONGSHEN'
            ]
        ],
        'model' => [
            'name' => 'Modèle',
            'type' => 'text',
            'title' => 'Get the exact name on LaCentrale'
        ],
        'versions' => [
            'name' => 'Version(s)',
            'type' => 'text',
            'title' => 'Get the exact name(s) on LaCentrale. Separate by comma'
        ],
        'category' => [
            'name' => 'Catégorie',
            'type' => 'list',
            'values' => [
                '' => '',
                'Voiture' => [
                    '4x4, SUV & Crossover' => '47',
                    'Citadine' => '40',
                    'Berline' => '41_42',
                    'Break' => '43',
                    'Cabriolet' => '46',
                    'Coupé' => '45',
                    'Monospace' => '44',
                    'Bus et minibus' => '82',
                    'Fourgonnette' => '85',
                    'Fourgon (< 3,5 tonnes)' => '81',
                    'Pick-up' => '50',
                    'Voiture société, commerciale' => '80',
                    'Sans permis' => '48',
                    'Camion (> 3,5 tonnes)' => '83',
                ],
                'Camion/Pickup' => [
                    'Camion (> 3,5 tonnes)' => '83',
                    'Fourgon (< 3,5 tonnes)' => '81',
                    'Bus et minibus' => '82',
                    'Fourgonnette' => '85',
                    'Pick-up' => '50',
                    'Voiture société, commerciale' => '80'
                ],
                'Moto' => [
                    'Custom' => '60',
                    'Offroad' => '61',
                    'Roadster' => '62',
                    'GT' => '63',
                    'Mini moto' => '64',
                    'Mobylette' => '65',
                    'Supermotard' => '66',
                    'Trail' => '67',
                    'Side-car' => '69',
                    'Sportive' => '68'
                ],
                'Caravane/Camping-car' => [
                    'Caravane' => '423',
                    'Profilé' => '506',
                    'Fourgon aménagé' => '507',
                    'Intégral' => '508',
                    'Capucine' => '510'
                ]
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
        'location' => [
            'name' => 'CP ou département',
            'type' => 'number',
            'title' => 'Only one'
        ],
        'distance' => [
            'name' => 'Rayon de recherche',
            'type' => 'list',
            'values' => [
                '' => '',
                '10 km' => '1',
                '20 km' => '2',
                '50 km' => '3',
                '100 km' => '4',
                '200 km' => '5'
            ]
        ],
        'region' => [
            'name' => 'Région',
            'type' => 'list',
            'values' => [
                '' => '',
                'Auvergne-Rhône-Alpes' => 'FR-ARA',
                'Bourgogne-Franche-Comté' => 'FR-BFC',
                'Bretagne' => 'FR-BRE',
                'Centre-Val de Loire' => 'FR-CVL',
                'Corse' => 'FR-COR',
                'Grand Est' => 'FR-GES',
                'Hauts-de-France' => 'FR-HDF',
                'Île-de-France' => 'FR-IDF',
                'Normandie' => 'FR-NOR',
                'Nouvelle-Aquitaine' => 'FR-PAC',
                'Occitanie' => 'FR-PDL',
                'Pays de la Loire' => 'FR-OCC',
                'Provence-Alpes-Côte d\'Azur' => 'FR-NAQ'
            ]
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
                'Diesel' => 'dies',
                'Essence' => 'ess',
                'Électrique' => 'elec',
                'Hybride' => 'hyb',
                'GPL' => 'gpl',
                'Bioéthanol' => 'eth',
                'Autre' => 'alt'
            ]
        ],
        'gearbox' => [
            'name' => 'Boite de vitesse',
            'type' => 'list',
            'values' => [
                '' => '',
                'Boite automatique' => 'AUTO',
                'Boite mécanique' => 'MANUAL'
            ]
        ],
        'doors' => [
            'name' => 'Nombre de portes',
            'type' => 'list',
            'values' => [
                '' => '',
                '2 portes' => '2',
                '3 portes' => '3',
                '4 portes' => '4',
                '5 portes' => '5',
                '6 portes ou plus' => '6'
            ]
        ],
        'firsthand' => [
            'name' => 'Première main',
            'type' => 'checkbox'
        ],
        'seller' => [
            'name' => 'Vendeur',
            'type' => 'list',
            'values' => [
                '' => '',
                'Particulier' => 'PART',
                'Professionel' => 'PRO'
            ]
        ],
        'sort' => [
            'name' => 'Tri',
            'type' => 'list',
            'values' => [
                'Prix (croissant)' => 'priceAsc',
                'Prix (décroissant)' => 'priceDesc',
                'Marque (croissant)' => 'makeAsc',
                'Marque (décroissant)' => 'makeDesc',
                'Kilométrage (croissant)' => 'mileageAsc',
                'Kilométrage (décroissant)' => 'mileageDesc',
                'Année (croissant)' => 'yearAsc',
                'Année (décroissant)' => 'yearDesc',
                'Département (croissant)' => 'visitPlaceAsc',
                'Département (décroissant)' => 'visitPlaceDesc'
            ]
        ],
    ]];

    public function collectData()
    {
        if (
            !empty($this->getInput('distance'))
            && is_null($this->getInput('location'))
        ) {
            returnClientError('You need a place ("CP ou département") to search arround.');
        }

        $params = [
            'vertical' => $this->getInput('type'),
            'makesModelsCommercialNames' => $this->getInput('brand') . ':' . $this->getInput('model'),
            'versions' => $this->getInput('versions'),
            'categories' => $this->getInput('category'),
            'priceMin' => $this->getInput('pricemin'),
            'priceMax' => $this->getInput('pricemax'),
            'dptCp' => $this->getInput('location'),
            'distance' => $this->getInput('distance'),
            'regions' => $this->getInput('region'),
            'mileageMin' => $this->getInput('mileagemin'),
            'mileageMax' => $this->getInput('mileagemax'),
            'yearMin' => $this->getInput('yearmin'),
            'yearMax' => $this->getInput('yearmax'),
            'cubicMin' => $this->getInput('cubiccapacitymin'),
            'cubicMax' => $this->getInput('cubiccapacitymax'),
            'energies' => $this->getInput('fuel'),
            'firstHand' => $this->getInput('firsthand') ? 'true' : 'false',
            'gearbox' => $this->getInput('gearbox'),
            'doors' => $this->getInput('doors'),
            'sortBy' => $this->getInput('sort')
        ];
        $url = sprintf('%slisting?%s', self::URI, http_build_query($params));
        $html = getSimpleHTMLDOM($url);

        $elements = $html->find('.adLineContainer');
        foreach ($elements as $element) {
            $item = [];
            $item['uri'] = trim(self::URI, '/') . $element->find('div > a', 0)->href;
            $item['title'] = $element->find('.searchCard__makeModel', 0)->plaintext;
            $item['sellerType'] = $element->find('.searchCard__customer', 0)->plaintext;
            $item['author'] = $item['sellerType'];
            $item['version'] = $element->find('.searchCard__version', 0)->plaintext;
            $item['price'] = $element->find('.searchCard__fieldPrice', 0)->plaintext;
            $item['year'] = $element->find('.searchCard__year', 0)->plaintext;
            $item['mileage'] = $element->find('.searchCard__mileage', 0)->plaintext;
            // The image is lazyloaded with ajax

            $item['content'] = '
			<br>Variation : ' . $item['version']
            . '<br>Prix : ' . $item['price']
            . '<br>Année : ' . $item['year']
            . '<br>Kilométrage : ' . $item['mileage']
            . '<br>Type de vendeur : ' . $item['sellerType'];

            $this->items[] = $item;
        }
    }
}
