<?php

class AnfrBridge extends BridgeAbstract
{
    const NAME = 'ANFR';
    const URI = 'https://data.anfr.fr/';
    const DESCRIPTION = 'Fetches data from the French administration "Agence Nationale des Fréquences".';
    const CACHE_TIMEOUT = 604800; // 7d
    const MAINTAINER = 'quent1';
    const PARAMETERS = [
        'Données sur les réseaux mobiles' => [
            'departement' => [
                'name' => 'Département',
                'type' => 'list',
                'values' => [
                    'Tous' => null,
                    'Ain' => '001',
                    'Aisne' => '002',
                    'Allier' => '003',
                    'Alpes-de-Haute-Provence' => '004',
                    'Hautes-Alpes' => '005',
                    'Alpes-Maritimes' => '006',
                    'Ardèche' => '007',
                    'Ardennes' => '008',
                    'Ariège' => '009',
                    'Aube' => '010',
                    'Aude' => '011',
                    'Aveyron' => '012',
                    'Bouches-du-Rhône' => '013',
                    'Calvados' => '014',
                    'Cantal' => '015',
                    'Charente' => '016',
                    'Charente-Maritime' => '017',
                    'Cher' => '018',
                    'Corrèze' => '019',
                    'Corse-du-Sud' => '02A',
                    'Haute-Corse' => '02B',
                    'Côte-d\'Or' => '021',
                    'Côtes-d\'Armor' => '022',
                    'Creuse' => '023',
                    'Dordogne' => '024',
                    'Doubs' => '025',
                    'Drôme' => '026',
                    'Eure' => '027',
                    'Eure-et-Loir' => '028',
                    'Finistère' => '029',
                    'Gard' => '030',
                    'Haute-Garonne' => '031',
                    'Gers' => '032',
                    'Gironde' => '033',
                    'Hérault' => '034',
                    'Ille-et-Vilaine' => '035',
                    'Indre' => '036',
                    'Indre-et-Loire' => '037',
                    'Isère' => '038',
                    'Jura' => '039',
                    'Landes' => '040',
                    'Loir-et-Cher' => '041',
                    'Loire' => '042',
                    'Haute-Loire' => '043',
                    'Loire-Atlantique' => '044',
                    'Loiret' => '045',
                    'Lot' => '046',
                    'Lot-et-Garonne' => '047',
                    'Lozère' => '048',
                    'Maine-et-Loire' => '049',
                    'Manche' => '050',
                    'Marne' => '051',
                    'Haute-Marne' => '052',
                    'Mayenne' => '053',
                    'Meurthe-et-Moselle' => '054',
                    'Meuse' => '055',
                    'Morbihan' => '056',
                    'Moselle' => '057',
                    'Nièvre' => '058',
                    'Nord' => '059',
                    'Oise' => '060',
                    'Orne' => '061',
                    'Pas-de-Calais' => '062',
                    'Puy-de-Dôme' => '063',
                    'Pyrénées-Atlantiques' => '064',
                    'Hautes-Pyrénées' => '065',
                    'Pyrénées-Orientales' => '066',
                    'Bas-Rhin' => '067',
                    'Haut-Rhin' => '068',
                    'Rhône' => '069',
                    'Haute-Saône' => '070',
                    'Saône-et-Loire' => '071',
                    'Sarthe' => '072',
                    'Savoie' => '073',
                    'Haute-Savoie' => '074',
                    'Paris' => '075',
                    'Seine-Maritime' => '076',
                    'Seine-et-Marne' => '077',
                    'Yvelines' => '078',
                    'Deux-Sèvres' => '079',
                    'Somme' => '080',
                    'Tarn' => '081',
                    'Tarn-et-Garonne' => '082',
                    'Var' => '083',
                    'Vaucluse' => '084',
                    'Vendée' => '085',
                    'Vienne' => '086',
                    'Haute-Vienne' => '087',
                    'Vosges' => '088',
                    'Yonne' => '089',
                    'Territoire de Belfort' => '090',
                    'Essonne' => '091',
                    'Hauts-de-Seine' => '092',
                    'Seine-Saint-Denis' => '093',
                    'Val-de-Marne' => '094',
                    'Val-d\'Oise' => '095',
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
            'generation' => [
                'name' => 'Génération',
                'type' => 'list',
                'values' => [
                    'Tous' => null,
                    '2G' => '2G',
                    '3G' => '3G',
                    '4G' => '4G',
                    '5G' => '5G',
                ]
            ],
            'operateur' => [
                'name' => 'Opérateur',
                'type' => 'list',
                'values' => [
                    'Tous' => null,
                    'Bouygues Télécom' => 'BOUYGUES TELECOM',
                    'Dauphin Télécom' => 'DAUPHIN TELECOM',
                    'Digiciel' => 'DIGICEL',
                    'Free Caraïbes' => 'FREE CARAIBES',
                    'Free Mobile' => 'FREE MOBILE',
                    'GLOBALTEL' => 'GLOBALTEL',
                    'Office des postes et télécommunications de Nouvelle Calédonie' => 'Gouv Nelle Calédonie (OPT)',
                    'Maore Mobile' => 'MAORE MOBILE',
                    'ONATi' => 'ONATI',
                    'Orange' => 'ORANGE',
                    'Outremer Telecom' => 'OUTREMER TELECOM',
                    'Vodafone polynésie' => 'PMT/VODAPHONE',
                    'SFR' => 'SFR',
                    'SPM Télécom' => 'SPM TELECOM',
                    'Service des Postes et Télécommunications de Polynésie Française' => 'Gouv Nelle Calédonie (OPT)',
                    'SRR' => 'SRR',
                    'Station étrangère' => 'Station étrangère',
                    'Telco OI' => 'TELCO IO',
                    'United Telecommunication Services Caraïbes' => 'UTS Caraibes',
                    'Ora Mobile' => 'VITI SAS',
                    'Zeop' => 'ZEOP'
                ]
            ],
            'statut' => [
                'name' => 'Statut',
                'type' => 'list',
                'values' => [
                    'Tous' => null,
                    'En service' => 'En service',
                    'Projet approuvé' => 'Projet approuvé',
                    'Techniquement opérationnel' => 'Techniquement opérationnel',
                ]
            ]
        ]
    ];

    public function collectData()
    {
        $urlParts = [
            'id' => 'observatoire_2g_3g_4g',
            'resource_id' => '88ef0887-6b0f-4d3f-8545-6d64c8f597da',
            'fields' => 'id,adm_lb_nom,sta_nm_dpt,emr_lb_systeme,generation,date_maj,sta_nm_anfr,adr_lb_lieu,adr_lb_add1,adr_lb_add2,adr_lb_add3,adr_nm_cp,statut',
            'rows' => 10000
        ];

        if (!empty($this->getInput('departement'))) {
            $urlParts['refine.sta_nm_dpt'] = urlencode($this->getInput('departement'));
        }

        if (!empty($this->getInput('generation'))) {
            $urlParts['refine.generation'] = $this->getInput('generation');
        }

        if (!empty($this->getInput('operateur'))) {
            // http_build_query() already does urlencoding so this call is redundant
            $urlParts['refine.adm_lb_nom'] = urlencode($this->getInput('operateur'));
        }

        if (!empty($this->getInput('statut'))) {
            $urlParts['refine.statut'] = urlencode($this->getInput('statut'));
        }

        // API seems to not play well with urlencoded data
        $url = urljoin(static::URI, '/d4c/api/records/1.0/download/?' . urldecode(http_build_query($urlParts)));

        $json = getContents($url);
        $data = Json::decode($json, false);
        $records = $data->records;
        $frequenciesByStation = [];
        foreach ($records as $record) {
            if (!isset($frequenciesByStation[$record->fields->sta_nm_anfr])) {
                $street = sprintf(
                    '%s %s %s',
                    $record->fields->adr_lb_add1 ?? '',
                    $record->fields->adr_lb_add2 ?? '',
                    $record->fields->adr_lb_add3 ?? ''
                );
                $frequenciesByStation[$record->fields->sta_nm_anfr] = [
                    'id' => $record->fields->sta_nm_anfr,
                    'operator' => $record->fields->adm_lb_nom,
                    'frequencies' => [],
                    'lastUpdate' => 0,
                    'address' => [
                        'street' => trim($street),
                        'postCode' => $record->fields->adr_nm_cp,
                        'city' => $record->fields->adr_lb_lieu
                    ]
                ];
            }

            $frequenciesByStation[$record->fields->sta_nm_anfr]['frequencies'][] = [
                'generation' => $record->fields->generation,
                'frequency' => $record->fields->emr_lb_systeme,
                'status' => $record->fields->statut,
                'updatedAt' => strtotime($record->fields->date_maj),
            ];

            $frequenciesByStation[$record->fields->sta_nm_anfr]['lastUpdate'] = max(
                $frequenciesByStation[$record->fields->sta_nm_anfr]['lastUpdate'],
                strtotime($record->fields->date_maj)
            );
        }

        usort($frequenciesByStation, static fn ($a, $b) => $b['lastUpdate'] <=> $a['lastUpdate']);

        foreach ($frequenciesByStation as $station) {
            $title = sprintf(
                '[%s] Mise à jour de la station n°%s à %s (%s)',
                $station['operator'],
                $station['id'],
                $station['address']['city'],
                $station['address']['postCode']
            );

            $array_reduce = array_reduce($station['frequencies'], static function ($carry, $frequency) {
                return sprintf('%s<li>%s : %s</li>', $carry, $frequency['frequency'], $frequency['status']);
            }, '');

            $content = sprintf(
                '<h1>Adresse complète</h1><p>%s<br>%s<br>%s</p><h1>Fréquences</h1><p><ul>%s</ul></p>',
                $station['address']['street'],
                $station['address']['postCode'],
                $station['address']['city'],
                $array_reduce
            );

            $this->items[] = [
                'uid'       => $station['id'],
                'timestamp' => $station['lastUpdate'],
                'title'     => $title,
                'content'   => $content,
            ];
        }
    }
}