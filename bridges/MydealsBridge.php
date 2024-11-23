<?php

class MydealsBridge extends PepperBridgeAbstract
{
    const NAME = 'Mydealz bridge';
    const URI = 'https://www.mydealz.de/';
    const DESCRIPTION = 'Zeigt die Deals von mydealz.de';
    const MAINTAINER = 'sysadminstory';
    const PARAMETERS = [
        'Suche nach Stichworten' => [
            'q' => [
                'name' => 'Stichworten',
                'type' => 'text',
                'exampleValue' => 'lamp',
                'required' => true
            ],
            'hide_expired' => [
                'name' => 'Abgelaufenes ausblenden',
                'type' => 'checkbox',
            ],
            'hide_local' => [
                'name' => 'Lokales ausblenden',
                'type' => 'checkbox',
                'title' => 'Deals im physischen Geschäft ausblenden',
            ],
            'priceFrom' => [
                'name' => 'Minimaler Preis',
                'type' => 'text',
                'title' => 'Minmaler Preis in Euros',
                'required' => false
            ],
            'priceTo' => [
                'name' => 'Maximaler Preis',
                'type' => 'text',
                'title' => 'maximaler Preis in Euro',
                'required' => false
            ],
        ],

        'Deals pro Gruppen' => [
            'group' => [
                'name' => 'Gruppen',
                'type' => 'text',
                'exampleValue' => 'dsl',
                'title' => 'Gruppenname in der URL: Der einzugebende Gruppenname steht nach "https://www.mydealz.de/gruppe/" und vor einem "?".
Beispiel: Wenn die URL der Gruppe, die im Browser angezeigt wird, :
https://www.mydealz.de/gruppe/dsl?sortBy=temp
Dann geben Sie ein:
dsl',
                ],
            'order' => [
                'name' => 'sortieren nach',
                'type' => 'list',
                'title' => 'Sortierung der deals',
                'values' => [
                    'Vom heißesten zum kältesten Deal' => '-hot',
                    'Vom jüngsten Deal zum ältesten' => '-new',
                ]
            ],
        ],
        'Überwachung Diskussion' => [
            'url' => [
                'name' => 'URL der Diskussion',
                'type' => 'text',
                'required' => true,
                'title' => 'URL-Diskussion zu überwachen: https://www.mydealz.de/diskussion/title-123',
                'exampleValue' => 'https://www.mydealz.de/diskussion/anleitung-wie-schreibe-ich-einen-deal-1658317',
                ],
            'only_with_url' => [
                'name' => 'Kommentare ohne URL ausschließen',
                'type' => 'checkbox',
                'title' => 'Kommentare, die keine URL enthalten, im Feed ausschließen',
                'defaultValue' => false,
                ]
            ]
    ];

    public $lang = [
        'bridge-uri' => self::URI,
        'bridge-name' => self::NAME,
        'context-keyword' => 'Suche nach Stichworten',
        'context-group' => 'Deals pro Gruppen',
        'context-talk' => 'Überwachung Diskussion',
        'uri-group' => 'gruppe/',
        'uri-deal' => 'deals/',
        'uri-merchant' => 'search/gutscheine?merchant-id=',
        'request-error' => 'Could not request mydeals',
        'thread-error' => 'Die ID der Diskussion kann nicht ermittelt werden. Überprüfen Sie die eingegebene URL',
        'currency' => '€',
        'price' => 'Preis',
        'shipping' => 'Versand',
        'origin' => 'Ursprung',
        'discount' => 'Rabatte',
        'title-keyword' => 'Suche',
        'title-group' => 'Gruppe',
        'title-talk' => 'Überwachung Diskussion',
        'deal-type' => 'Angebotsart',
        'localdeal' => 'Lokales Angebot',
        'context-hot' => '-hot',
        'context-new' => '-new',
    ];
}
