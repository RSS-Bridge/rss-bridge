<?php

class IdealoBridge extends BridgeAbstract
{
    const NAME = 'idealo.de / idealo.fr / idealo.es Bridge';
    const URI = 'https://www.idealo.de';
    const DESCRIPTION = 'Tracks the price for a product on idealo.de / idealo.fr / idealo.es. Pricealarm if specific price is set';
    const MAINTAINER = 'SebLaus';
    const CACHE_TIMEOUT = 60 * 30; // 30 min
    const PARAMETERS = [
        [
            'Link' => [
                'name'          => 'idealo.de / idealo.fr / idealo.es Link to productpage',
                'required'      => true,
                'exampleValue'  => 'https://www.idealo.de/preisvergleich/OffersOfProduct/202007367_-s7-pro-ultra-roborock.html'
            ],
            'ExcludeNew' => [
                'name' => 'Priceupdate: Do not track new items',
                'type' => 'checkbox',
                'value' => 'c'
            ],
            'ExcludeUsed' => [
                'name' => 'Priceupdate: Do not track used items',
                'type' => 'checkbox',
                'value' => 'uc'
            ],
            'MaxPriceNew' => [
                'name'          => 'Pricealarm: Maximum price for new Product',
                'type'          => 'number'
            ],
            'MaxPriceUsed' => [
                'name'          => 'Pricealarm: Maximum price for used Product',
                'type'          => 'number'
            ],
        ]
    ];

    public function getIcon()
    {
        return 'https://cdn.idealo.com/storage/ids-assets/ico/favicon.ico';
    }

    /**
     * Returns the RSS Feed title when a RSS feed is rendered
     * @return string the RSS feed Title
     */
    private function getFeedTitle()
    {
        $cacheDuration = 604800;
        $link = $this->getInput('Link');
        $keyTITLE = $link . 'TITLE';
        $product = $this->loadCacheValue($keyTITLE);

        // The cache does not contain the title of the bridge, we must get it and save it in the cache
        if ($product === null) {
            $header = [
                'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2.1 Safari/605.1.15'
            ];
            $html = getSimpleHTMLDOM($link, $header);
            $product = $html->find('.oopStage-title', 0)->find('span', 0)->plaintext;
            $this->saveCacheValue($keyTITLE, $product);
        }

        $MaxPriceUsed = $this->getInput('MaxPriceUsed');
        $MaxPriceNew = $this->getInput('MaxPriceNew');
        $titleParts = [];

        $titleParts[] = $product;

        // Add Max Prices to the title
        if ($MaxPriceUsed !== null) {
            $titleParts[] = 'Max Price Used : ' . $MaxPriceUsed . '€';
        }
        if ($MaxPriceNew !== null) {
            $titleParts[] = 'Max Price New : ' . $MaxPriceNew . '€';
        }

        $title = implode(' ', $titleParts);


        return $title . ' - ' . $this::NAME;
    }

    /**
     * Returns the Price as float
     * @return float rhe price converted in float
     */
    private function convertPriceToFloat($price)
    {
        // Every price is stored / displayed as "xxx,xx €", but PHP can't convert it as float

        if ($price !== null) {
            // Convert comma as dot
            $price = str_replace(',', '.', $price);
            // Remove the '€' char
            $price = str_replace('€', '', $price);
            // Convert to float
            return floatval($price);
        } else {
            return $price;
        }
    }

    /**
     * Returns the Price Trend emoji
     * @return string the Price Trend Emoji
     */
    private function getPriceTrend($NewPrice, $OldPrice)
    {
        $NewPrice = $this->convertPriceToFloat($NewPrice);
        $OldPrice = $this->convertPriceToFloat($OldPrice);
        // In case there is no old Price, then show no trend
        if ($OldPrice === null || $OldPrice == 0) {
            $trend = '';
        } else if ($NewPrice > $OldPrice) {
            $trend = '&#x2197;';
        } else if ($NewPrice == $OldPrice) {
            $trend = '&#x27A1;';
        } else if ($NewPrice < $OldPrice) {
            $trend = '&#x2198;';
        }
        return $trend;
    }
    public function collectData()
    {
        // Needs header with user-agent to function properly.
        $header = [
            'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2.1 Safari/605.1.15'
        ];

        $link = $this->getInput('Link');
        $html = getSimpleHTMLDOM($link, $header);

        // Get Productname
        $titleobj = $html->find('.oopStage-title', 0);
        $Productname = $titleobj->find('span', 0)->plaintext;

        // Create product specific Cache Keys with the link
        $KeyNEW = $link;
        $KeyNEW .= 'NEW';

        $KeyUSED = $link;
        $KeyUSED .= 'USED';

        // Load previous Price
        $OldPriceNew = $this->loadCacheValue($KeyNEW);
        $OldPriceUsed = $this->loadCacheValue($KeyUSED);

        // First button contains the new price. Found at oopStage-conditionButton-wrapper-text class (.)
        $ActualNewPrice = $html->find('div[id=oopStage-conditionButton-new]', 0);
        // Second Button contains the used product price
        $ActualUsedPrice = $html->find('div[id=oopStage-conditionButton-used]', 0);
        // Get the first item of the offers list to have an option if there is no New/Used Button available
        $altPrice = $html->find('.productOffers-listItemOfferPrice', 0);

        if ($ActualNewPrice) {
            $PriceNew = $ActualNewPrice->find('strong', 0)->plaintext;
            // Save current price
            $this->saveCacheValue($KeyNEW, $PriceNew);
        } else if ($altPrice) {
            // Get price from first List item if no New/used Buttons available
            $PriceNew = trim($altPrice->plaintext);
            $this->saveCacheValue($KeyNEW, $PriceNew);
        } else if (($ActualNewPrice === null || $altPrice === null) && $ActualUsedPrice !== null) {
            // In case there is no actual New Price and a Used Price exists, then delete the previous value in the cache
            $this->cache->delete($this->getShortName() . '_' . $KeyNEW);
        }

        // Second Button contains the used product price
        if ($ActualUsedPrice) {
            $PriceUsed = $ActualUsedPrice->find('strong', 0)->plaintext;
            // Save current price
            $this->saveCacheValue($KeyUSED, $PriceUsed);
        } else if ($ActualUsedPrice === null && ($ActualNewPrice !== null || $altPrice !== null)) {
            // In case there is no actual Used Price and a New Price exists, then delete the previous value in the cache
            $this->cache->delete($this->getShortName() . '_' . $KeyUSED);
        }

        // Only continue if a price has changed and there exists a New, Used or Alternative price (sometimes no new Price _and_ Used Price are shown)
        if (!($ActualNewPrice === null && $ActualUsedPrice === null && $altPrice === null) && ($PriceNew != $OldPriceNew || $PriceUsed != $OldPriceUsed)) {
            // Get Product Image
            $image = $html->find('.datasheet-cover-image', 0)->src;

            $content = '';

            // Generate Content
            if (isset($PriceNew) && $this->convertPriceToFloat($PriceNew) > 0) {
                $content .= sprintf('<p><b>Price New:</b><br>%s %s</p>', $PriceNew, $this->getPriceTrend($PriceNew, $OldPriceNew));
                $content .= "<p><b>Price New before:</b><br>$OldPriceNew</p>";
            }

            if ($this->getInput('MaxPriceNew') != '') {
                $content .= sprintf('<p><b>Max Price New:</b><br>%s,00 €</p>', $this->getInput('MaxPriceNew'));
            }

            if (isset($PriceUsed) && $this->convertPriceToFloat($PriceUsed) > 0) {
                $content .= sprintf('<p><b>Price Used:</b><br>%s %s</p>', $PriceUsed, $this->getPriceTrend($PriceUsed, $OldPriceUsed));
                $content .= "<p><b>Price Used before:</b><br>$OldPriceUsed</p>";
            }

            if ($this->getInput('MaxPriceUsed') != '') {
                $content .= sprintf('<p><b>Max Price Used:</b><br>%s,00 €</p>', $this->getInput('MaxPriceUsed'));
            }

            $content .= "<img src=$image>";


            $now = date('d/m/Y H:i');

            $Pricealarm = 'Pricealarm %s: %s %s - %s';

            // Currently under Max new price
            if ($this->getInput('MaxPriceNew') != '') {
                if (isset($PriceNew) && $this->convertPriceToFloat($PriceNew) < $this->getInput('MaxPriceNew')) {
                    $title = sprintf($Pricealarm, 'New', $PriceNew, $Productname, $now);
                    $item = [
                        'title'     => $title,
                        'uri'       => $link,
                        'content'   => $content,
                        'uid'       => md5($title)
                    ];
                    $this->items[] = $item;
                }
            }

            // Currently under Max used price
            if ($this->getInput('MaxPriceUsed') != '') {
                if (isset($PriceUsed) && $this->convertPriceToFloat($PriceUsed) < $this->getInput('MaxPriceUsed')) {
                    $title = sprintf($Pricealarm, 'Used', $PriceUsed, $Productname, $now);
                    $item = [
                        'title'     => $title,
                        'uri'       => $link,
                        'content'   => $content,
                        'uid'       => md5($title)
                    ];
                    $this->items[] = $item;
                }
            }

            // General Priceupdate Without any Max Price for new and Used product
            if ($this->getInput('MaxPriceUsed') == '' && $this->getInput('MaxPriceNew') == '') {
                // check if a relevant pricechange happened
                if (
                    (!$this->getInput('ExcludeNew') && $PriceNew != $OldPriceNew ) ||
                    (!$this->getInput('ExcludeUsed') && $PriceUsed != $OldPriceUsed )
                ) {
                    $title = 'Priceupdate! ';

                    if (!$this->getInput('ExcludeNew') && isset($PriceNew)) {
                        $title .= 'NEW' . $this->getPriceTrend($PriceNew, $OldPriceNew) . ' ';
                    }

                    if (!$this->getInput('ExcludeUsed') && isset($PriceUsed)) {
                        $title .= 'USED' . $this->getPriceTrend($PriceUsed, $OldPriceUsed) . ' ';
                    }
                    $title .= $Productname;
                    $title .= ' - ';
                    $title .= $now;

                    $item = [
                        'title'     => $title,
                        'uri'       => $link,
                        'content'   => $content,
                        'uid'       => md5($title)
                    ];
                    $this->items[] = $item;
                }
            }
        }
    }

    /**
     * Returns the RSS Feed title according to the parameters
     * @return string the RSS feed Tile
     */
    public function getName()
    {
        switch ($this->queriedContext) {
            case '0':
                return $this->getFeedTitle();
            default:
                return parent::getName();
        }
    }

    /**
     * Returns the RSS Feed URL according to the parameters
     * @return string the RSS feed URL
     */
    public function getURI()
    {
        switch ($this->queriedContext) {
            case '0':
                return $this->getInput('Link');
            default:
                return parent::getURI();
        }
    }
}
