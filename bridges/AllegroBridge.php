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
        'sessioncookie' => [
            'name' => 'The \'wdctx\' session cookie',
            'title' => 'Paste the value of the \'wdctx\' cookie from your browser if you want to prevent Allegro imposing rate limits',
            'pattern' => '^.{70,};?$',
            // phpcs:ignore
            'exampleValue' => 'v4.1-oCrmXTMqv2ppC21GTUCKLmUwRPP1ssQVALKuqwsZ1VXjcKgL2vO5TTRM5xMxS9GiyqxF1gAeyc-63dl0coUoBKXCXi_nAmr95yyqGpq2RAFoneZ4L399E8n6iYyemcuGARjAoSfjvLHJCEwvvHHynSgaxlFBu7hUnKfuy39zo9sSQdyTUjotJg3CAZ53q9v2raAnPCyGOAR4ytRILd9p24EJnxp7_oR0XbVPIo1hDa4WmjXFOxph8rHaO5tWd',
            'required' => false,
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

        $opts = [];

        // If a session cookie is provided
        if ($sessioncookie = $this->getInput('sessioncookie')) {
            $opts[CURLOPT_COOKIE] = 'wdctx=' . $sessioncookie;
        }

        $html = getSimpleHTMLDOM($url, [], $opts);

        # if no results found
        if ($html->find('.mzmg_6m.m9qz_yo._6a66d_-fJr5')) {
            return;
        }

        $results = $html->find('article[data-analytics-view-custom-context="REGULAR"]');

        if (!$this->getInput('includeSponsoredOffers')) {
            $results = array_merge($results, $html->find('article[data-analytics-view-custom-context="SPONSORED"]'));
        }

        if (!$this->getInput('includePromotedOffers')) {
            $results = array_merge($results, $html->find('article[data-analytics-view-custom-context="PROMOTED"]'));
        }

        foreach ($results as $post) {
            $item = [];

            $item['uid'] = $post->{'data-analytics-view-value'};

            $item_link = $post->find('a[href*="' . $item['uid'] . '"], a[href*="allegrolokalnie"]', 0);

            $item['uri'] = $item_link->href;

            $item['title'] = $item_link->find('img', 0)->alt;

            $image = $item_link->find('img', 0)->{'data-src'} ?: $item_link->find('img', 0)->src ?? false;

            if ($image) {
                $item['enclosures'] = [$image . '#.image'];
            }

            $price = $post->{'data-analytics-view-json-custom-price'};
            if ($price) {
                $priceDecoded = json_decode(html_entity_decode($price));
                $price = $priceDecoded->amount . ' ' . $priceDecoded->currency;
            }

            $descriptionPatterns = ['/<\s*dt[^>]*>\b/', '/<\/dt>/', '/<\s*dd[^>]*>\b/', '/<\/dd>/'];
            $descriptionReplacements = ['<span>', ':</span> ', '<strong>', '&emsp;</strong> '];
            $description = $post->find('.m7er_k4.mpof_5r.mpof_z0_s', 0)->innertext;
            $descriptionPretty = preg_replace($descriptionPatterns, $descriptionReplacements, $description);

            $pricingExtraInfo = array_filter($post->find('.mqu1_g3.mgn2_12'), function ($node) {
                return empty($node->find('.mvrt_0'));
            });

            $pricingExtraInfo = $pricingExtraInfo[0]->plaintext ?? '';

            $offerExtraInfo = array_map(function ($node) {
                return str_contains($node->plaintext, 'zapłać później') ? '' : $node->outertext;
            }, $post->find('div.mpof_ki.mwdn_1.mj7a_4.mgn2_12'));

            $isSmart = $post->find('img[alt="Smart!"]', 0) ?? false;
            if ($isSmart) {
                $pricingExtraInfo .= $isSmart->outertext;
            }

            $item['categories'] = [];
            $parameters = $post->find('dd');
            foreach ($parameters as $parameter) {
                if (in_array(strtolower($parameter->innertext), ['brak', 'nie'])) {
                    continue;
                }

                $item['categories'][] = $parameter->innertext;
            }

            $item['content'] = $descriptionPretty
                . '<div><strong>'
                . $price
                . '</strong></div><div>'
                . implode('</div><div>', $offerExtraInfo)
                . '</div><dl>'
                . $pricingExtraInfo
                . '</dl><hr>';

            $this->items[] = $item;
        }
    }
}
