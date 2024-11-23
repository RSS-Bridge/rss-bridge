<?php

class DealabsBridge extends PepperBridgeAbstract
{
    const NAME = 'Dealabs Bridge';
    const URI = 'https://www.dealabs.com/';
    const DESCRIPTION = 'Affiche les Deals de Dealabs';
    const MAINTAINER = 'sysadminstory';
    const PARAMETERS = [
        'Recherche par Mot(s) clé(s)' => [
            'q' => [
                'name' => 'Mot(s) clé(s)',
                'type' => 'text',
                'exampleValue' => 'lampe',
                'required' => true
            ],
            'hide_expired' => [
                'name' => 'Masquer les éléments expirés',
                'type' => 'checkbox',
            ],
            'hide_local' => [
                'name' => 'Masquer les deals locaux',
                'type' => 'checkbox',
                'title' => 'Masquer les deals en magasins physiques',
            ],
            'priceFrom' => [
                'name' => 'Prix minimum',
                'type' => 'text',
                'title' => 'Prix mnimum en euros',
                'required' => false
            ],
            'priceTo' => [
                'name' => 'Prix maximum',
                'type' => 'text',
                'title' => 'Prix maximum en euros',
                'required' => false
            ],
        ],

        'Deals par groupe' => [
            'group' => [
                'name' => 'Groupe',
                'type' => 'text',
                'exampleValue' => 'abonnements-internet',
                'title' => 'Nom du groupe dans l\'URL : Il faut entrer le nom du groupe qui est présent après "https://www.dealabs.com/groupe/" et avant tout éventuel "?"
Exemple : Si l\'URL du groupe affichées dans le navigateur est :
https://www.dealabs.com/groupe/abonnements-internet?sortBy=lowest_price
Il faut alors saisir :
abonnements-internet',
                ],
            'order' => [
                'name' => 'Trier par',
                'type' => 'list',
                'title' => 'Ordre de tri des deals',
                'values' => [
                    'Du deal le plus Hot au moins Hot' => '-hot',
                    'Du deal le plus récent au plus ancien' => '-nouveaux',
                ]
            ]
        ],
        'Surveillance Discussion' => [
            'url' => [
                'name' => 'URL de la discussion',
                'type' => 'text',
                'required' => true,
                'title' => 'URL discussion à surveiller: https://www.dealabs.com/discussions/titre-1234',
                'exampleValue' => 'https://www.dealabs.com/discussions/jeux-steam-gratuits-gleam-woobox-etc-1071415',
                ],

            'only_with_url' => [
                'name' => 'Exclure les commentaires sans URL',
                'type' => 'checkbox',
                'title' => 'Exclure les commentaires ne contenant pas d\'URL dans le flux',
                'defaultValue' => false,
                ]


            ]

    ];

    public $lang = [
        'bridge-uri' => self::URI,
        'bridge-name' => self::NAME,
        'context-keyword' => 'Recherche par Mot(s) clé(s)',
        'context-group' => 'Deals par groupe',
        'context-talk' => 'Surveillance Discussion',
        'uri-group' => 'groupe/',
        'uri-deal' => 'bons-plans/',
        'uri-merchant' => 'search/bons-plans?merchant-id=',
        'request-error' => 'Impossible de joindre Dealabs',
        'thread-error' => 'Impossible de déterminer l\'ID de la discussion. Vérifiez l\'URL que vous avez entré',
        'currency' => '€',
        'price' => 'Prix',
        'shipping' => 'Livraison',
        'origin' => 'Origine',
        'discount' => 'Réduction',
        'title-keyword' => 'Recherche',
        'title-group' => 'Groupe',
        'title-talk' => 'Surveillance Discussion',
        'deal-type' => 'Type de deal',
        'localdeal' => 'Deal Local',
        'context-hot' => '-hot',
        'context-new' => '-nouveaux',
    ];
}
