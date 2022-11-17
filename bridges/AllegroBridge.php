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
            'pattern' => '^.{250,};?$',
            // phpcs:ignore
            'exampleValue' => 'v4.1-oCrmXTMqv2ppC21GTUCKLmUwRPP1ssQVALKuqwsZ1VXjcKgL2vO5TTRM5xMxS9GiyqxF1gAeyc-63dl0coUoBKXCXi_nAmr95yyqGpq2RAFoneZ4L399E8n6iYyemcuGARjAoSfjvLHJCEwvvHHynSgaxlFBu7hUnKfuy39zo9sSQdyTUjotJg3CAZ53q9v2raAnPCyGOAR4ytRILd9p24EJnxp7_oR0XbVPIo1hDa4WmjXFOxph8rHaO5tWd',
            'required' => false,
        ],
        'includeSponsoredOffers' => [
            'type' => 'checkbox',
            'name' => 'Include Sponsored Offers'
        ]
    ]];

    public function getName()
    {
        parse_str(parse_url($this->getInput('url'), PHP_URL_QUERY), $fields);

        if ($query = array_key_exists('string', $fields) ? urldecode($fields['string']) : false) {
            return $query;
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

        $results = $html->find('._6a66d_V7Lel article');

        if (!$this->getInput('includeSponsoredOffers')) {
            $results = array_filter($results, function ($node) {
                return $node->{'data-analytics-view-label'} != 'showSponsoredItems';
            });
        }

        foreach ($results as $post) {
            $item = [];

            $item['uri'] = $post->find('._6a66d_LX75-', 0)->href;

//TODO: port this over, whatever it does, from https://github.com/MK-PL/AllegroRSS
//        if (arrayLinks.includes('events/clicks?')) {
//            let sponsoredLink = new URL(arrayLinks).searchParams.get('redirect')
//          arrayLinks = sponsoredLink.slice(0, sponsoredLink.indexOf('?'))
//        }

            $item['title'] = $post->find('._6a66d_LX75-', 0)->innertext;

            $item['uid'] = $post->{'data-analytics-view-value'};

            $descriptionPatterns = ['/<\s*dt[^>]*>\b/', '/<\/dt>/', '/<\s*dd[^>]*>\b/', '/<\/dd>/'];
            $descriptionReplacements = ['<span>', ':</span> ', '<strong>', '&emsp;</strong> '];
            $description = $post->find('.m7er_k4.mpof_5r.mpof_z0_s', 0)->innertext;
            $descriptionPretty = preg_replace($descriptionPatterns, $descriptionReplacements, $description);

            $buyNowAuction = $post->find('.mqu1_g3.mvrt_0.mgn2_12', 0)->innertext ?? '';
            $buyNowAuction = str_replace('</span><span', '</span> <span', $buyNowAuction);

            $auctionTimeLeft = $post->find('._6a66d_ImOzU', 0)->innertext ?? '';

            $price = $post->find('._6a66d_6R3iN', 0)->plaintext;
            $price = empty($auctionTimeLeft) ? $price : $price . '- kwota licytacji';

            $image = $post->find('._6a66d_44ioA img', 0)->{'data-src'} ?: $post->find('._6a66d_44ioA img', 0)->src ?? false;
            if ($image) {
                $item['enclosures'] = [$image . '#.image'];
            }

            $offerExtraInfo = array_filter($post->find('.mqu1_g3.mgn2_12'), function ($node) {
                return empty($node->find('.mvrt_0'));
            });

            $offerExtraInfo = $offerExtraInfo[0]->plaintext ?? '';

            $isSmart = $post->find('._6a66d_TC2Zk', 0)->innertext ?? '';
            if (str_contains($isSmart, 'z kurierem')) {
                $offerExtraInfo .= ', Smart z kurierem';
            } else {
                $offerExtraInfo .= ', Smart';
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
                . $auctionTimeLeft
                . '</div><div>'
                . $buyNowAuction
                . '</div><dl>'
                . $offerExtraInfo
                . '</dl><hr>';

            $this->items[] = $item;
        }
    }
}
