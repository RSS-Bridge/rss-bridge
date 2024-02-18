<?php

class IdealoBridge extends BridgeAbstract
{
    const NAME = 'Idealo.de Bridge';
    const URI = 'https://www.idealo.de';
    const DESCRIPTION = 'Tracks the price for a product on idealo.de. Pricealarm if specific price is set';
    const MAINTAINER = 'SebLaus';
    const CACHE_TIMEOUT = 60 * 30; // 30 min
    const PARAMETERS = [
        [
            'Link' => [
                'name'          => 'Idealo.de Link to productpage',
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

        // First button is new. Found at oopStage-conditionButton-wrapper-text class (.)
        $FirstButton = $html->find('.oopStage-conditionButton-wrapper-text', 0);
        if ($FirstButton) {
            $PriceNew = $FirstButton->find('strong', 0)->plaintext;
        }

        // Second Button is used
        $SecondButton = $html->find('.oopStage-conditionButton-wrapper-text', 1);
        if ($SecondButton) {
            $PriceUsed = $SecondButton->find('strong', 0)->plaintext;
        }

        // Only continue if a price has changed
        if ($PriceNew != $OldPriceNew || $PriceUsed != $OldPriceUsed) {
            // Get Product Image
            $image = $html->find('.datasheet-cover-image', 0)->src;

            // Generate Content
            if ($PriceNew > 1) {
                $content = "<p><b>Price New:</b><br>$PriceNew</p>";
                $content .= "<p><b>Price New before:</b><br>$OldPriceNew</p>";
            }

            if ($this->getInput('MaxPriceNew') != '') {
                $content .= sprintf('<p><b>Max Price Used:</b><br>%s,00 €</p>', $this->getInput('MaxPriceNew'));
            }

            if ($PriceUsed > 1) {
                $content .= "<p><b>Price Used:</b><br>$PriceUsed</p>";
                $content .= "<p><b>Price Used before:</b><br>$OldPriceUsed</p>";
            }

            if ($this->getInput('MaxPriceUsed') != '') {
                $content .= sprintf('<p><b>Max Price Used:</b><br>%s,00 €</p>', $this->getInput('MaxPriceUsed'));
            }

            $content .= "<img src=$image>";


            $now = date('d.m.j H:m');

            $Pricealarm = 'Pricealarm %s: %s %s %s';

            // Currently under Max new price
            if ($this->getInput('MaxPriceNew') != '') {
                if ($PriceNew < $this->getInput('MaxPriceNew')) {
                    $title = sprintf($Pricealarm, 'Used', $PriceNew, $Productname, $now);
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
                if ($PriceUsed < $this->getInput('MaxPriceUsed')) {
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

            // General Priceupdate
            if ($this->getInput('MaxPriceUsed') == '' && $this->getInput('MaxPriceNew') == '') {
                // check if a relevant pricechange happened
                if (
                    (!$this->getInput('ExcludeNew') && $PriceNew != $OldPriceNew ) ||
                    (!$this->getInput('ExcludeUsed') && $PriceUsed != $OldPriceUsed )
                ) {
                    $title .= 'Priceupdate! ';

                    if (!$this->getInput('ExcludeNew')) {
                        if ($PriceNew < $OldPriceNew) {
                            $title .= 'NEW:&#11015 '; // Arrow Down Emoji
                        }
                        if ($PriceNew > $OldPriceNew) {
                            $title .= 'NEW:&#11014 '; // Arrow Up Emoji
                        }
                    }


                    if (!$this->getInput('ExcludeUsed')) {
                        if ($PriceUsed < $OldPriceUsed) {
                            $title .= 'USED:&#11015 '; // Arrow Down Emoji
                        }
                        if ($PriceUsed > $OldPriceUsed) {
                            $title .= 'USED:&#11014 '; // Arrow Up Emoji
                        }
                    }
                    $title .= $Productname;
                    $title .= ' ';
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

        // Save current price
        $this->saveCacheValue($KeyNEW, $PriceNew);
        $this->saveCacheValue($KeyUSED, $PriceUsed);
    }
}
