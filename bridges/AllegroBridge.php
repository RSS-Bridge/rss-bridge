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
        'includeSponsoredOffers' => [
            'type' => 'checkbox',
            'name' => 'Include Sponsored Offers'
        ]
    ]];

    public function collectData()
    {
        # make sure we order by the most recently listed offers
        $url = preg_replace('/([?&])order=[^&]+(&|$)/', '$1', $this->getInput('url'));
        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'order=n';

        $html = getSimpleHTMLDOM($url);

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

            $uri = $post->find('._6a66d_LX75-', 0)->href;

//TODO: port this over, whatever it does, from https://github.com/MK-PL/AllegroRSS
//        if (arrayLinks.includes('events/clicks?')) {
//            let sponsoredLink = new URL(arrayLinks).searchParams.get('redirect')
//          arrayLinks = sponsoredLink.slice(0, sponsoredLink.indexOf('?'))
//        }

            $title = $post->find('._6a66d_LX75-', 0)->innertext;

            $descriptionPatterns = ['/<\s*dt[^>]*>\b/', '/<\/dt>/', '/<\s*dd[^>]*>\b/', '/<\/dd>/'];
            $descriptionReplacements = ['<span>', ':</span> ', '<strong>', ',</strong> '];
            $description = $post->find('.m7er_k4.mpof_5r.mpof_z0_s', 0)->innertext;
            $descriptionPretty = preg_replace($descriptionPatterns, $descriptionReplacements, $description);

            $buyNowAuction = str_replace('</span><span', '</span> <span', $post->find('.mqu1_g3.mvrt_0.mgn2_12', 0)->innertext);

            $auctionTimeLeft = $post->find('._6a66d_ImOzU', 0)->innertext;

            $price = $post->find('._6a66d_6R3iN', 0)->plaintext;
            $price = empty($auctionTimeLeft) ? $price : $price . '- kwota licytacji';

            $image = $post->find('._6a66d_44ioA img', 0)->{'data-src'} ?: $post->find('._6a66d_44ioA img', 0)->src;

            $offerExtraInfo = array_filter($post->find('.mqu1_g3.mgn2_12'), function ($node) {
                return empty($node->find('.mvrt_0'));
            })[0]->plaintext;

            if (str_contains($post->find('._6a66d_TC2Zk', 0)->innertext, 'z kurierem')) {
                $offerExtraInfo .= ', Smart z kurierem';
            } else {
                $offerExtraInfo .= ', Smart';
            }

            $item['uri'] = $uri;
            $item['title'] = $title;
            $item['content'] = $descriptionPretty
                . '<div><strong>'
                . $price
                . '</strong></div><div>'
                . $auctionTimeLeft
                . '</div><div>'
                . $buyNowAuction
                . '</div><dl>'
                . $offerExtraInfo
                . '</dl><div><img src="'
                . $image
                . '" alt="'
                . $title
                . '"></div><hr>';

            $this->items[] = $item;
        }
    }
}
