<?php

class SteamBridge extends BridgeAbstract
{
    const NAME = 'Steam Bridge';
    const URI = 'https://store.steampowered.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns apps list';
    const MAINTAINER = 'jacknumber';
    const PARAMETERS = [
        'Wishlist' => [
            'userid' => [
                'name' => 'Steamid64 (find it on steamid.io)',
                'title' => 'User ID (17 digits). Find your user ID with steamid.io or steamidfinder.com',
                'required' => true,
                'exampleValue' => '76561198821231205',
                'pattern' => '[0-9]{17}',
            ],
            'only_discount' => [
                'name' => 'Only discount',
                'type' => 'checkbox',
            ]
        ]
    ];

    public function collectData()
    {
        $userid = $this->getInput('userid');

        $sourceUrl = self::URI . 'wishlist/profiles/' . $userid . '/wishlistdata?p=0';
        $sort = [];

        $json = getContents($sourceUrl);

        $appsData = json_decode($json);

        foreach ($appsData as $id => $element) {
            $appType = $element->type;
            $appIsBuyable = 0;
            $appHasDiscount = 0;
            $appIsFree = 0;

            if ($element->subs) {
                $appIsBuyable = 1;
                $priceBlock = str_get_html($element->subs[0]->discount_block);
                $appPrice = str_replace('--', '00', $priceBlock->find('.discount_final_price', 0)->plaintext);

                if ($element->subs[0]->discount_pct) {
                    $appHasDiscount = 1;
                    $discountBlock = str_get_html($element->subs[0]->discount_block);
                    $appDiscountValue = $discountBlock->find('.discount_pct', 0)->plaintext;
                    $appOldPrice = $discountBlock->find('.discount_original_price', 0)->plaintext;
                } else {
                    if ($this->getInput('only_discount')) {
                        continue;
                    }
                }
            } else {
                if ($this->getInput('only_discount')) {
                    continue;
                }

                if (isset($element->free) && $element->free = 1) {
                    $appIsFree = 1;
                }
            }

            $coverUrl = str_replace('_292x136', '', strtok($element->capsule, '?'));
            $picturesPath = pathinfo($coverUrl)['dirname'] . '/';

            $item = [];
            $item['uri'] = "http://store.steampowered.com/app/$id/";
            $item['title'] = $element->name;
            $item['type'] = $appType;
            $item['cover'] = $coverUrl;
            $item['timestamp'] = $element->added;
            $item['isBuyable'] = $appIsBuyable;
            $item['hasDiscount'] = $appHasDiscount;
            $item['isFree'] = $appIsFree;
            $item['priority'] = $element->priority;

            if ($appIsBuyable) {
                $item['price'] = floatval(str_replace(',', '.', $appPrice));
                $item['content'] = $appPrice;
            }

            if ($appIsFree) {
                $item['content'] = 'Free';
            }

            if ($appHasDiscount) {
                $item['discount']['value'] = $appDiscountValue;
                $item['discount']['oldPrice'] = $appOldPrice;
                $item['content'] = '<s>' . $appOldPrice . '</s> <b>' . $appPrice . '</b> (' . $appDiscountValue . ')';
            }

            $item['enclosures'] = [];
            $item['enclosures'][] = $coverUrl;

            foreach ($element->screenshots as $screenshotFileName) {
                $item['enclosures'][] = $picturesPath . $screenshotFileName;
            }

            $sort[$id] = $element->priority;

            $this->items[] = $item;
        }

        array_multisort($sort, SORT_ASC, $this->items);
    }
}
