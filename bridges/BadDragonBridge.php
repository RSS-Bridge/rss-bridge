<?php

class BadDragonBridge extends BridgeAbstract
{
    const NAME = 'Bad Dragon Bridge';
    const URI = 'https://bad-dragon.com/';
    const CACHE_TIMEOUT = 300; // 5min
    const DESCRIPTION = 'Returns sales or new clearance items';
    const MAINTAINER = 'Roliga';
    const PARAMETERS = [
        'Sales' => [
        ],
        'Clearance' => [
            'ready_made' => [
                'name' => 'Ready Made',
                'type' => 'checkbox'
            ],
            'flop' => [
                'name' => 'Flops',
                'type' => 'checkbox'
            ],
            'skus' => [
                'name' => 'Products',
                'exampleValue' => 'chanceflared, crackers',
                'title' => 'Comma separated list of product SKUs'
            ],
            'onesize' => [
                'name' => 'One-Size',
                'type' => 'checkbox'
            ],
            'mini' => [
                'name' => 'Mini',
                'type' => 'checkbox'
            ],
            'small' => [
                'name' => 'Small',
                'type' => 'checkbox'
            ],
            'medium' => [
                'name' => 'Medium',
                'type' => 'checkbox'
            ],
            'large' => [
                'name' => 'Large',
                'type' => 'checkbox'
            ],
            'extralarge' => [
                'name' => 'Extra Large',
                'type' => 'checkbox'
            ],
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'All' => 'all',
                    'Accessories' => 'accessories',
                    'Merchandise' => 'merchandise',
                    'Dildos' => 'insertable',
                    'Masturbators' => 'penetrable',
                    'Packers' => 'packer',
                    'Lil\' Squirts' => 'shooter',
                    'Lil\' Vibes' => 'vibrator',
                    'Wearables' => 'wearable'
                ],
                'defaultValue' => 'all',
            ],
            'soft' => [
                'name' => 'Soft Firmness',
                'type' => 'checkbox'
            ],
            'med_firm' => [
                'name' => 'Medium Firmness',
                'type' => 'checkbox'
            ],
            'firm' => [
                'name' => 'Firm',
                'type' => 'checkbox'
            ],
            'split' => [
                'name' => 'Split Firmness',
                'type' => 'checkbox'
            ],
            'maxprice' => [
                'name' => 'Max Price',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 300
            ],
            'minprice' => [
                'name' => 'Min Price',
                'type' => 'number',
                'defaultValue' => 0
            ],
            'cumtube' => [
                'name' => 'Cumtube',
                'type' => 'checkbox'
            ],
            'suctionCup' => [
                'name' => 'Suction Cup',
                'type' => 'checkbox'
            ],
            'noAccessories' => [
                'name' => 'No Accessories',
                'type' => 'checkbox'
            ]
        ]
    ];

    /*
     * This sets index $strFrom (or $strTo if set) in $outArr to 'on' if
     * $inArr[$param] contains $strFrom.
     * It is used for translating BD's shop filter URLs into something we can use.
     *
     * For the query '?type[]=ready_made&type[]=flop' we would have an array like:
     * Array (
     *     [type] => Array (
     *             [0] => ready_made
     *             [1] => flop
     *         )
     * )
     * which could be translated into:
     * Array (
     *     [ready_made] => on
     *     [flop] => on
     * )
     * */
    private function setParam($inArr, &$outArr, $param, $strFrom, $strTo = null)
    {
        if (isset($inArr[$param]) && in_array($strFrom, $inArr[$param])) {
            $outArr[($strTo ?: $strFrom)] = 'on';
        }
    }

    public function detectParameters($url)
    {
        $params = [];

        // Sale
        $regex = '/^(https?:\/\/)?bad-dragon\.com\/sales/';
        if (preg_match($regex, $url, $matches) > 0) {
            return $params;
        }

        // Clearance
        $regex = '/^(https?:\/\/)?bad-dragon\.com\/shop\/clearance/';
        if (preg_match($regex, $url, $matches) > 0) {
            parse_str(parse_url($url, PHP_URL_QUERY), $urlParams);

            $this->setParam($urlParams, $params, 'type', 'ready_made');
            $this->setParam($urlParams, $params, 'type', 'flop');

            if (isset($urlParams['skus'])) {
                $skus = [];
                foreach ($urlParams['skus'] as $sku) {
                    is_string($sku) && $skus[] = $sku;
                    is_array($sku) && $skus[] = $sku[0];
                }
                $params['skus'] = implode(',', $skus);
            }

            $this->setParam($urlParams, $params, 'sizes', 'onesize');
            $this->setParam($urlParams, $params, 'sizes', 'mini');
            $this->setParam($urlParams, $params, 'sizes', 'small');
            $this->setParam($urlParams, $params, 'sizes', 'medium');
            $this->setParam($urlParams, $params, 'sizes', 'large');
            $this->setParam($urlParams, $params, 'sizes', 'extralarge');

            if (isset($urlParams['category'])) {
                $params['category'] = strtolower($urlParams['category']);
            } else {
                $params['category'] = 'all';
            }

            $this->setParam($urlParams, $params, 'firmnessValues', 'soft');
            $this->setParam($urlParams, $params, 'firmnessValues', 'medium', 'med_firm');
            $this->setParam($urlParams, $params, 'firmnessValues', 'firm');
            $this->setParam($urlParams, $params, 'firmnessValues', 'split');

            if (isset($urlParams['price'])) {
                isset($urlParams['price']['max'])
                    && $params['maxprice'] = $urlParams['price']['max'];
                isset($urlParams['price']['min'])
                    && $params['minprice'] = $urlParams['price']['min'];
            }

            isset($urlParams['cumtube'])
                && $urlParams['cumtube'] === '1'
                && $params['cumtube'] = 'on';
            isset($urlParams['suctionCup'])
                && $urlParams['suctionCup'] === '1'
                && $params['suctionCup'] = 'on';
            isset($urlParams['noAccessories'])
                && $urlParams['noAccessories'] === '1'
                && $params['noAccessories'] = 'on';

            return $params;
        }

        return null;
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Sales':
                return 'Bad Dragon Sales';
            case 'Clearance':
                return 'Bad Dragon Clearance Search';
            default:
                return parent::getName();
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Sales':
                return self::URI . 'sales';
            case 'Clearance':
                return $this->inputToURL();
            default:
                return parent::getURI();
        }
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'Sales':
                $sales = json_decode(getContents(self::URI . 'api/sales'));

                foreach ($sales as $sale) {
                    $item = [];

                    $item['title'] = $sale->title;
                    $item['timestamp'] = strtotime($sale->startDate);

                    $item['uri'] = $this->getURI() . '/' . $sale->slug;

                    $contentHTML = '<p><img src="' . $sale->image->url . '"></p>';
                    if (isset($sale->endDate)) {
                        $contentHTML .= '<p><b>This promotion ends on '
                        . gmdate('M j, Y \a\t g:i A T', strtotime($sale->endDate))
                        . '</b></p>';
                    } else {
                        $contentHTML .= '<p><b>This promotion never ends</b></p>';
                    }
                    $ul = false;
                    $content = json_decode($sale->content);
                    foreach ($content->blocks as $block) {
                        switch ($block->type) {
                            case 'header-one':
                                $contentHTML .= '<h1>' . $block->text . '</h1>';
                                break;
                            case 'header-two':
                                $contentHTML .= '<h2>' . $block->text . '</h2>';
                                break;
                            case 'header-three':
                                $contentHTML .= '<h3>' . $block->text . '</h3>';
                                break;
                            case 'unordered-list-item':
                                if (!$ul) {
                                    $contentHTML .= '<ul>';
                                    $ul = true;
                                }
                                $contentHTML .= '<li>' . $block->text . '</li>';
                                break;
                            default:
                                if ($ul) {
                                    $contentHTML .= '</ul>';
                                    $ul = false;
                                }
                                $contentHTML .= '<p>' . $block->text . '</p>';
                                break;
                        }
                    }
                    $item['content'] = $contentHTML;

                    $this->items[] = $item;
                }
                break;
            case 'Clearance':
                $toyData = json_decode(getContents($this->inputToURL(true)));

                $productList = json_decode(getContents(self::URI
                . 'api/inventory-toy/product-list'));

                foreach ($toyData->toys as $toy) {
                    $item = [];

                    $item['uri'] = $this->getURI()
                        . '#'
                        . $toy->id;
                    $item['timestamp'] = strtotime($toy->created);

                    foreach ($productList as $product) {
                        if ($product->sku == $toy->sku) {
                            $item['title'] = $product->name;
                            break;
                        }
                    }

                    // images
                    $content = '<p>';
                    foreach ($toy->images as $image) {
                        $content .= '<a href="'
                        . $image->fullFilename
                        . '"><img src="'
                        . $image->thumbFilename
                        . '" /></a>';
                    }
                    // price
                    $content .= '</p><p><b>Price:</b> $'
                    . $toy->price
                    // size
                    . '<br /><b>Size:</b> '
                    . $toy->size
                    // color
                    . '<br /><b>Color:</b> '
                    . $toy->color
                    // features
                    . '<br /><b>Features:</b> '
                    . ($toy->suction_cup ? 'Suction cup' : '')
                    . ($toy->suction_cup && $toy->cumtube ? ', ' : '')
                    . ($toy->cumtube ? 'Cumtube' : '')
                    . ($toy->suction_cup || $toy->cumtube ? '' : 'None');
                    // firmness
                    $firmnessTexts = [
                    '2' => 'Extra soft',
                    '3' => 'Soft',
                    '5' => 'Medium',
                    '8' => 'Firm'
                    ];
                    $firmnesses = explode('/', $toy->firmness);
                    if (count($firmnesses) === 2) {
                        $content .= '<br /><b>Firmness:</b> '
                        . $firmnessTexts[$firmnesses[0]]
                        . ', '
                        . $firmnessTexts[$firmnesses[1]];
                    } else {
                        $content .= '<br /><b>Firmness:</b> '
                        . $firmnessTexts[$firmnesses[0]];
                    }
                    // flop
                    if ($toy->type === 'flop') {
                        $content .= '<br /><b>Flop reason:</b> '
                        . $toy->flop_reason;
                    }
                    $content .= '</p>';
                    $item['content'] = $content;

                    $enclosures = [];
                    foreach ($toy->images as $image) {
                        $enclosures[] = $image->fullFilename;
                    }
                    $item['enclosures'] = $enclosures;

                    $categories = [];
                    $categories[] = $toy->sku;
                    $categories[] = $toy->type;
                    $categories[] = $toy->size;
                    if ($toy->cumtube) {
                        $categories[] = 'cumtube';
                    }
                    if ($toy->suction_cup) {
                        $categories[] = 'suction_cup';
                    }
                    $item['categories'] = $categories;

                    $this->items[] = $item;
                }
                break;
        }
    }

    private function inputToURL($api = false)
    {
        $url = self::URI;
        $url .= ($api ? 'api/inventory-toys?' : 'shop/clearance?');

        // Default parameters
        $url .= 'limit=60';
        $url .= '&page=1';
        $url .= '&sort[field]=created';
        $url .= '&sort[direction]=desc';

        // Product types
        $url .= ($this->getInput('ready_made') ? '&type[]=ready_made' : '');
        $url .= ($this->getInput('flop') ? '&type[]=flop' : '');

        // Product names
        foreach (array_filter(explode(',', $this->getInput('skus'))) as $sku) {
            $url .= '&skus[]=' . urlencode(trim($sku));
        }

        // Size
        $url .= ($this->getInput('onesize') ? '&sizes[]=onesize' : '');
        $url .= ($this->getInput('mini') ? '&sizes[]=mini' : '');
        $url .= ($this->getInput('small') ? '&sizes[]=small' : '');
        $url .= ($this->getInput('medium') ? '&sizes[]=medium' : '');
        $url .= ($this->getInput('large') ? '&sizes[]=large' : '');
        $url .= ($this->getInput('extralarge') ? '&sizes[]=extralarge' : '');

        // Category
        $url .= ($this->getInput('category') ? '&category='
            . urlencode($this->getInput('category')) : '');

        // Firmness
        if ($api) {
            $url .= ($this->getInput('soft') ? '&firmnessValues[]=3' : '');
            $url .= ($this->getInput('med_firm') ? '&firmnessValues[]=5' : '');
            $url .= ($this->getInput('firm') ? '&firmnessValues[]=8' : '');
            if ($this->getInput('split')) {
                $url .= '&firmnessValues[]=3/5';
                $url .= '&firmnessValues[]=3/8';
                $url .= '&firmnessValues[]=8/3';
                $url .= '&firmnessValues[]=5/8';
                $url .= '&firmnessValues[]=8/5';
            }
        } else {
            $url .= ($this->getInput('soft') ? '&firmnessValues[]=soft' : '');
            $url .= ($this->getInput('med_firm') ? '&firmnessValues[]=medium' : '');
            $url .= ($this->getInput('firm') ? '&firmnessValues[]=firm' : '');
            $url .= ($this->getInput('split') ? '&firmnessValues[]=split' : '');
        }

        // Price
        $url .= ($this->getInput('maxprice') ? '&price[max]='
            . $this->getInput('maxprice') : '&price[max]=300');
        $url .= ($this->getInput('minprice') ? '&price[min]='
            . $this->getInput('minprice') : '&price[min]=0');

        // Features
        $url .= ($this->getInput('cumtube') ? '&cumtube=1' : '');
        $url .= ($this->getInput('suctionCup') ? '&suctionCup=1' : '');
        $url .= ($this->getInput('noAccessories') ? '&noAccessories=1' : '');

        return $url;
    }
}
