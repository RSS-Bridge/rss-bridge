<?php

class BodaccBridge extends BridgeAbstract
{
    const NAME = 'BODACC';
    const URI = 'https://bodacc-datadila.opendatasoft.com/';
    const DESCRIPTION = 'Fetches announces from the French Government "Bulletin Officiel Des Annonces Civiles et Commerciales".';
    const CACHE_TIMEOUT = 86400;
    const MAINTAINER = 'quent1';
    const PARAMETERS = [
        'Annonces commerciales' => [
            'departement' => [
                'name' => 'Département',
                'type' => 'list',
                'values' => [
                    'Tous' => null,
                    'Ain' => '01',
                    'Aisne' => '02',
                    'Allier' => '03',
                    'Alpes-de-Haute-Provence' => '04',
                    'Hautes-Alpes' => '05',
                    'Alpes-Maritimes' => '06',
                    'Ardèche' => '07',
                    'Ardennes' => '08',
                    'Ariège' => '09',
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
                    'Val-d\'Oise' => '95',
                    'Guadeloupe' => '971',
                    'Martinique' => '972',
                    'Guyane' => '973',
                    'La Réunion' => '974',
                    'Saint-Pierre-et-Miquelon' => '975',
                    'Mayotte' => '976',
                    'Saint-Barthélemy' => '977',
                    'Saint-Martin' => '978',
                    'Terres australes et antarctiques françaises' => '984',
                    'Wallis-et-Futuna' => '986',
                    'Polynésie française' => '987',
                    'Nouvelle-Calédonie' => '988',
                    'Île de Clipperton' => '989'
                ]
            ],
            'famille' => [
                'name' => 'Famille',
                'type' => 'list',
                'values' => [
                    'Toutes' => null,
                    'Annonces diverses' => 'divers',
                    'Créations' => 'creation',
                    'Dépôts des comptes' => 'dpc',
                    'Immatriculations' => 'immatriculation',
                    'Modifications diverses' => 'modification',
                    'Procédures collectives' => 'collective',
                    'Procédures de conciliation' => 'conciliation',
                    'Procédures de rétablissement professionnel' => 'retablissement_professionnel',
                    'Radiations' => 'radiation',
                    'Ventes et cessions' => 'vente'
                ]
            ],
            'type' => [
                'name' => 'Type',
                'type' => 'list',
                'values' => [
                    'Tous' => null,
                    'Avis initial' => 'annonce',
                    'Avis d\'annulation' => 'annulation',
                    'Avis rectificatif' => 'rectificatif'
                ]
            ]
        ]
    ];

    public function collectData()
    {
        $parameters = [
            'select'    => 'id,dateparution,typeavis_lib,familleavis_lib,commercant,ville,cp',
            'order_by'  => 'id desc',
            'limit'     => 50,
        ];

        $where = [];
        if (!empty($this->getInput('departement'))) {
            $where[] = 'numerodepartement="' . $this->getInput('departement') . '"';
        }

        if (!empty($this->getInput('famille'))) {
            $where[] = 'familleavis="' . $this->getInput('famille') . '"';
        }

        if (!empty($this->getInput('type'))) {
            $where[] = 'typeavis="' . $this->getInput('type') . '"';
        }

        if ($where !== []) {
            $parameters['where'] = implode(' and ', $where);
        }

        $url = urljoin(self::URI, '/api/explore/v2.1/catalog/datasets/annonces-commerciales/records?' . http_build_query($parameters));

        $data = Json::decode(getContents($url), false);

        foreach ($data->results as $result) {
            if (
                !isset(
                    $result->id,
                    $result->dateparution,
                    $result->typeavis_lib,
                    $result->familleavis_lib,
                    $result->commercant,
                    $result->ville,
                    $result->cp
                )
            ) {
                continue;
            }

            $title = sprintf(
                '[%s] %s - %s à %s (%s)',
                $result->typeavis_lib,
                $result->familleavis_lib,
                $result->commercant,
                $result->ville,
                $result->cp
            );

            $this->items[] = [
                'uid'       => $result->id,
                'timestamp' => strtotime($result->dateparution),
                'title'     => $title,
            ];
        }
    }
}
