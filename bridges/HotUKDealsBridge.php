<?php

class HotUKDealsBridge extends PepperBridgeAbstract
{
    const NAME = 'HotUKDeals bridge';
    const URI = 'https://www.hotukdeals.com/';
    const DESCRIPTION = 'Return the HotUKDeals search result using keywords';
    const MAINTAINER = 'sysadminstory';
    const PARAMETERS = [
        'Search by keyword(s))' => [
            'q' => [
                'name' => 'Keyword(s)',
                'type' => 'text',
                'exampleValue' => 'lamp',
                'required' => true
            ],
            'hide_expired' => [
                'name' => 'Hide expired deals',
                'type' => 'checkbox',
            ],
            'hide_local' => [
                'name' => 'Hide local deals',
                'type' => 'checkbox',
                'title' => 'Hide deals in physical store',
            ],
            'priceFrom' => [
                'name' => 'Minimal Price',
                'type' => 'text',
                'title' => 'Minmal Price in Pounds',
                'required' => false
            ],
            'priceTo' => [
                'name' => 'Maximum Price',
                'type' => 'text',
                'title' => 'Maximum Price in Pounds',
                'required' => false
            ],
        ],

        'Deals per group' => [
            'group' => [
                'name' => 'Group',
                'type' => 'text',
                'exampleValue' => 'broadband',
                'title' => 'Group name in the URL : The group name that must be entered is present after "https://www.hotukdeals.com/tag/" and before any "?".
Example: If the URL of the group displayed in the browser is :
https://www.hotukdeals.com/tag/broadband?sortBy=temp
Then enter :
broadband',
            ],
            'order' => [
                'name' => 'Order by',
                'type' => 'list',
                'title' => 'Sort order of deals',
                'values' => [
                    'From the most to the least hot deal' => '-hot',
                    'From the most recent deal to the oldest' => '-new',
                ]
            ]
        ],
        'Discussion Monitoring' => [
            'url' => [
                'name' => 'Discussion URL',
                'type' => 'text',
                'required' => true,
                'title' => 'Discussion URL to monitor. Ex: https://www.hotukdeals.com/discussions/title-123',
                'exampleValue' => 'https://www.hotukdeals.com/discussions/the-hukd-lego-thread-3599357',
                ],
            'only_with_url' => [
                'name' => 'Exclude comments without URL',
                'type' => 'checkbox',
                'title' => 'Exclude comments that does not contains URL in the feed',
                'defaultValue' => false,
                ]
            ]


    ];

    public $lang = [
        'bridge-uri' => self::URI,
        'bridge-name' => self::NAME,
        'context-keyword' => 'Search by keyword(s))',
        'context-group' => 'Deals per group',
        'context-talk' => 'Discussion Monitoring',
        'uri-group' => 'tag/',
        'uri-deal' => 'deals/',
        'uri-merchant' => 'search/deals?merchant-id=',
        'request-error' => 'Could not request HotUKDeals',
        'thread-error' => 'Unable to determine the thread ID. Check the URL you entered',
        'currency' => '£',
        'price' => 'Price',
        'shipping' => 'Shipping',
        'origin' => 'Origin',
        'discount' => 'Discount',
        'title-keyword' => 'Search',
        'title-group' => 'Group',
        'title-talk' => 'Discussion Monitoring',
        'deal-type' => 'Deal Type',
        'localdeal' => 'Local deal',
        'context-hot' => '-hot',
        'context-new' => '-new',
    ];
}
