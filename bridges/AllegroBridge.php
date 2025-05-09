<?php

class AllegroBridge extends BridgeAbstract
{
    const NAME = 'Allegro';
    const URI = 'https://www.allegro.pl';
    const DESCRIPTION = 'Returns the search results from the Allegro.pl shopping and bidding portal';
    const MAINTAINER = 'wrobelda';
    const PARAMETERS = [[
        'url' => [
            'name' => 'Search URL',
            'title' => 'Copy the URL from your browser\'s address bar after searching for your items and paste it here',
            'exampleValue' => 'https://allegro.pl/kategoria/swieze-warzywa-cebula-318660',
            'required' => true,
        ],
        'cookie' => [
            'name' => 'The complete cookie value',
            'title' => 'Paste the cookie value from your browser, otherwise 403 gets returned',
            'required' => true,
        ],
        'includeSponsoredOffers' => [
            'type' => 'checkbox',
            'name' => 'Include Sponsored Offers',
            'defaultValue' => 'checked'
        ],
        'includePromotedOffers' => [
            'type' => 'checkbox',
            'name' => 'Include Promoted Offers',
            'defaultValue' => 'checked'
        ]
    ]];

    public function getName()
    {
        $url = $this->getInput('url');
        if (!$url) {
            return parent::getName();
        }
        $parsedUrl = parse_url($url, PHP_URL_QUERY);
        if (!$parsedUrl) {
            return parent::getName();
        }
        parse_str($parsedUrl, $fields);

        if (array_key_exists('string', $fields)) {
            $f = urldecode($fields['string']);
        } else {
            $f = false;
        }
        if ($f) {
            return $f;
        }

        return parent::getName();
    }

    public function getURI()
    {
        return $this->getInput('url') ?? parent::getURI();
    }

    public function collectData()
    {
        # make sure we order by the most recently listed offers
        $url = preg_replace('/([?&])order=[^&]+(&|$)/', '$1', $this->getInput('url'));
        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'order=n';

        $html = getContents($url, [], [CURLOPT_COOKIE => $this->getInput('cookie')]);

        $storeData = null;
        if (preg_match('/<script[^>]*>\s*(\{\s*?"__listing_StoreState".*\})\s*<\/script>/i', $html, $match)) {
            $data = json_decode($match[1], true);
            $storeData = $data['__listing_StoreState'] ?? null;
        }

        foreach ($storeData['items']['elements'] as $elements) {
            if (!array_key_exists('offerId', $elements)) {
                continue;
            }
            if (!$this->getInput('includeSponsoredOffers') && $elements['isSponsored']) {
                continue;
            }
            if (!$this->getInput('includePromotedOffers') && $elements['promoted']) {
                continue;
            }

            $item = [];
            $item['uid'] = $elements['offerId'];
            $item['uri'] = $elements['url'];
            $item['title'] = $elements['alt'];

            $image = $elements['photos'][0]['medium'];
            if ($image) {
                $item['enclosures'] = [$image . '#.image'];
            }

            $price = $elements['price']['mainPrice']['amount'];
            $currency = $elements['price']['mainPrice']['currency'];
            $sellerType = $elements['seller']['title'];

            $item['categories'] = [$sellerType];

            $description = '';
            foreach ($elements['parameters'] as $parameter) {
                $item['categories'] = array_merge($item['categories'], $parameter['values']);
                $description .= '<dt>' . $parameter['name'] . ': ' . implode(',', $parameter['values']) . '</dt>';
            }

            $item['content'] = '<div><strong>'
                . $price . ' ' . $currency
                . '</strong></div><dl><dt>'
                . $sellerType . '</dt>'
                . $description
                . '</dl><hr>';

            $this->items[] = $item;
        }
    }
}

