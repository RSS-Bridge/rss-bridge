<?php

class EBayBridge extends BridgeAbstract
{
    const NAME = 'eBay';
    const DESCRIPTION = 'Returns the search results from the eBay auctioning platforms';
    const URI = 'https://www.eBay.com';
    const MAINTAINER = 'wrobelda';
    const PARAMETERS = [[
        'url' => [
            'name' => 'Search URL',
            'title' => 'Copy the URL from your browser\'s address bar after searching for your items and paste it here',
            'pattern' => '^(https:\/\/)?(www.)?ebay\.(com|com\.au|at|be|ca|ch|cn|es|fr|de|com\.hk|ie|it|com\.my|nl|ph|pl|com\.sg|co\.uk).*$',
            'exampleValue' => 'https://www.ebay.com/sch/i.html?_nkw=atom+rss',
            'required' => true,
        ]
    ]];

    public function getURI()
    {
        if ($this->getInput('url')) {
            # make sure we order by the most recently listed offers
            $uri = trim(preg_replace('/([?&])_sop=[^&]+(&|$)/', '$1', $this->getInput('url')), '?&/');
            $uri .= (parse_url($uri, PHP_URL_QUERY) ? '&' : '?') . '_sop=10';

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
            return $searchQuery[0];
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

        $results = $html->find('ul.srp-results > li.s-item');
        foreach ($results as $listing) {
            $item = [];

            // Remove "NEW LISTING" label, we sort by the newest, so this is redundant
            foreach ($listing->find('.LIGHT_HIGHLIGHT') as $new_listing_label) {
                $new_listing_label->remove();
            }

            $listingTitle = $listing->find('.s-item__title', 0);
            if ($listingTitle) {
                $item['title'] = $listingTitle->plaintext;
            }

            $subtitle = implode('', $listing->find('.s-item__subtitle'));

            $listingUrl = $listing->find('.s-item__link', 0);
            if ($listingUrl) {
                $item['uri'] = $listingUrl->href;
            } else {
                $item['uri'] = null;
            }

            if (preg_match('/.*\/itm\/(\d+).*/i', $item['uri'], $matches)) {
                $item['uid'] = $matches[1];
            }

            $priceDom = $listing->find('.s-item__details > .s-item__detail > .s-item__price', 0);
            $price = $priceDom->plaintext ?? 'N/A';

            $shippingFree = $listing->find('.s-item__details > .s-item__detail > .s-item__freeXDays', 0)->plaintext ?? '';
            $localDelivery = $listing->find('.s-item__details > .s-item__detail > .s-item__localDelivery', 0)->plaintext ?? '';
            $logisticsCost = $listing->find('.s-item__details > .s-item__detail > .s-item__logisticsCost', 0)->plaintext ?? '';

            $location = $listing->find('.s-item__details > .s-item__detail > .s-item__location', 0)->plaintext ?? '';

            $sellerInfo = $listing->find('.s-item__seller-info-text', 0)->plaintext ?? '';

            $image = $listing->find('.s-item__image-wrapper > img', 0);
            if ($image) {
                // Not quite sure why append fragment here
                $imageUrl = $image->src . '#.image';
                $item['enclosures'] = [$imageUrl];
            }

            $item['content'] = <<<CONTENT
<p>$sellerInfo $location</p>
<p><span style="font-weight:bold">$price</span> $shippingFree $localDelivery $logisticsCost<span></span></p>
<p>$subtitle</p>
CONTENT;
            $this->items[] = $item;
        }
    }
}
