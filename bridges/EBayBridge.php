<?php

class EBayBridge extends BridgeAbstract
{
    const NAME = 'eBay';
    const DESCRIPTION = 'Returns the search results from the eBay auctioning platforms';
    const URI = 'https://www.eBay.com';
    const MAINTAINER = 'NotsoanoNimus, wrobelda';
    const PARAMETERS = [[
        'url' => [
            'name' => 'Search URL',
            'title' => 'Copy the URL from your browser\'s address bar after searching for your items and paste it here',
            'pattern' => '^(https:\/\/)?(www\.)?(befr\.|benl\.)?ebay\.(com|com\.au|at|be|ca|ch|cn|es|fr|de|com\.hk|ie|it|com\.my|nl|ph|pl|com\.sg|co\.uk)\/.*$',
            'exampleValue' => 'https://www.ebay.com/sch/i.html?_nkw=atom+rss',
            'required' => true,
        ],
        'includesSearchLink' => [
            'name' => 'Include Original Search Link',
            'title' => 'Whether or not each feed item should include the original search query link to eBay which was used to find the given listing.',
            'type' => 'checkbox',
            'defaultValue' => false,
        ],
    ]];

    public function getURI()
    {
        if ($this->getInput('url')) {
            # make sure we order by the most recently listed offers
            $uri = trim(preg_replace('/([?&])_sop=[^&]+(&|$)/', '$1', $this->getInput('url')), '?&/');
            $uri .= (parse_url($uri, PHP_URL_QUERY) ? '&' : '?') . '_sop=10';

            // Ensure the List View is used instead of the Gallery View.
            $uri = trim(preg_replace('/[?&]_dmd=[^&]+(&|$)/i', '$1', $uri), '?&/');
            $uri .= '&_dmd=1';

            return $uri;
        } else {
            return parent::getURI();
        }
    }

    public function getName()
    {
        $url = $this->getInput('url');
        if (!$url) {
            return parent::getName();
        }
        $urlQueries = explode('&', parse_url($url, PHP_URL_QUERY));

        $searchQuery = array_reduce($urlQueries, function ($q, $p) {
            if (preg_match('/^_nkw=(.+)$/i', $p, $matches)) {
                $q[] = str_replace('+', ' ', urldecode($matches[1]));
            }

            return $q;
        });

        if ($searchQuery) {
            return 'eBay - ' . $searchQuery[0];
        }

        return parent::getName();
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        // Remove any unsolicited results, e.g. "Results matching fewer words"
        foreach ($html->find('ul.srp-results > li.srp-river-answer--REWRITE_START ~ li') as $inexactMatches) {
            $inexactMatches->remove();
        }

        // Remove "NEW LISTING" labels: we sort by the newest, so this is redundant.
        foreach ($html->find('.LIGHT_HIGHLIGHT') as $new_listing_label) {
            $new_listing_label->remove();
        }

        $results = $html->find('ul.srp-results > li.s-item');
        foreach ($results as $listing) {
            $item = [];

            // Define a closure to shorten the ugliness of querying the current listing.
            $find = function ($query, $altText = '') use ($listing) {
                return $listing->find($query, 0)->plaintext ?? $altText;
            };

            $item['title'] = $find('.s-item__title');
            if (!$item['title']) {
                // Skip entries where the title cannot be found (for w/e reason).
                continue;
            }

            // It appears there may be more than a single 'subtitle' subclass in the listing. Collate them.
            $subtitles = $listing->find('.s-item__subtitle');
            if (is_array($subtitles)) {
                $subtitle = trim(implode(' ', array_column($subtitles, 'plaintext')));
            } else {
                $subtitle = trim($subtitles->plaintext ?? '');
            }

            // Get the listing's link and uid.
            $itemUri = $listing->find('.s-item__link', 0);
            if ($itemUri) {
                $item['uri'] = $itemUri->href;
            }
            if (preg_match('/.*\/itm\/(\d+).*/i', $item['uri'], $matches)) {
                $item['uid'] = $matches[1];
            }

            // Price should be fetched on its own so we can provide the alt text without complication.
            $price = $find('.s-item__price', '[NO PRICE]');

            // Map a list of dynamic variable names to their subclasses within the listing.
            //   This is just a bit of sugar to make this cleaner and more maintainable.
            $propertyMappings = [
                'additionalPrice'   => '.s-item__additional-price',
                'discount'          => '.s-item__discount',
                'shippingFree'      => '.s-item__freeXDays',
                'localDelivery'     => '.s-item__localDelivery',
                'logisticsCost'     => '.s-item__logisticsCost',
                'location'          => '.s-item__location',
                'obo'               => '.s-item__formatBestOfferEnabled',
                'sellerInfo'        => '.s-item__seller-info-text',
                'bids'              => '.s-item__bidCount',
                'timeLeft'          => '.s-item__time-left',
                'timeEnd'           => '.s-item__time-end',
            ];

            foreach ($propertyMappings as $k => $v) {
                $$k = $find($v);
            }

            // When an additional price detail or discount is defined, create the 'discountLine'.
            if ($additionalPrice || $discount) {
                $discountLine = '<br /><em>('
                    . trim($additionalPrice ?? '')
                    . '; ' . trim($discount ?? '')
                    . ')</em>';
            } else {
                $discountLine = '';
            }

            // Prepend the time-left info with a comma if the right details were found.
            $timeInfo = trim($timeLeft . ' ' . $timeEnd);
            if ($timeInfo) {
                $timeInfo = ', ' . $timeInfo;
            }

            // Set the listing type.
            if ($bids) {
                $listingTypeDetails = "Auction: {$bids}{$timeInfo}";
            } else {
                $listingTypeDetails = 'Buy It Now';
            }

            // Acquire the listing's primary image and atach it.
            $image = $listing->find('.s-item__image-wrapper > img', 0);
            if ($image) {
                // Not quite sure why append fragment here
                $imageUrl = $image->src . '#.image';
                $item['enclosures'] = [$imageUrl];
            }

            // Include the original search link, if specified.
            if ($this->getInput('includesSearchLink')) {
                $searchLink = '<p><small><a target="_blank" href="' . e($this->getURI()) . '">View Search</a></small></p>';
            } else {
                $searchLink = '';
            }

            // Build the final item's content to display and add the item onto the list.
            $item['content'] = <<<CONTENT
<p>$sellerInfo $location</p>
<p><strong>$price</strong> $obo ($listingTypeDetails)
    $discountLine
    <br /><small>$shippingFree $localDelivery $logisticsCost</small></p>
<p>{$subtitle}</p>
$searchLink
CONTENT;

            $this->items[] = $item;
        }
    }
}
