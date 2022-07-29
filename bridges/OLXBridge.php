<?php

class OLXBridge extends BridgeAbstract
{
    const NAME = 'OLX';
    const DESCRIPTION = 'Returns the search results from the OLX auctioning platforms';
    const URI = 'https://www.olx.com';
    const MAINTAINER = 'wrobelda';
    const PARAMETERS = [[
        'url' => [
            'name' => 'Search URL',
            'title' => 'Copy the URL from your browser\'s address bar after searching for your items and paste it here',
            'exampleValue' => 'https://www.olx.pl/d/oferty/q-cebula/',
            'required' => true,
        ],
        'includePostsWithoutPricetag' => [
            'type' => 'checkbox',
            'name' => 'Include posts without price tag'
        ],
        'includeFeaturedPosts' => [
            'type' => 'checkbox',
            'name' => 'Include featured posts'
        ],
        'shippingOfferedOnly' => [
            'type' => 'checkbox',
            'name' => 'Only posts with shipping offered'
        ]
    ]];

    public function getURI()
    {
        $scheme = parse_url($this->getInput('url'), PHP_URL_SCHEME);
        $host = parse_url($this->getInput('url'), PHP_URL_HOST);

        if (strpos($host, 'olx')) {
            return $scheme . '://' . $host;
        }

        return parent::getURI();
    }
    public function collectData()
    {
        # make sure we order by the most recently listed offers
        $url = trim(preg_replace('/([?&])search%5Border%5D=[^&]+(&|$)/', '$1', $this->getInput('url')), '?&');
        $url = preg_replace('/([?&])view=[^&]+(&|$)/', '', $url);
        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'search%5Border%5D=created_at:desc';

        $html = getSimpleHTMLDOM($url);
        $html = defaultLinkTo($html, $this->getURI());

        $results = $html->find(".listing-grid-container div[data-cy='l-card']");

        foreach ($results as $post) {
            $item = [];

            if (!$this->getInput('includeFeaturedPosts') && $post->find('div[data-testid="adCard-featured"]', 0)) {
                continue;
            }

            $price = $post->find('p[data-testid="ad-price"]', 0)->plaintext;
            if (!$this->getInput('includePostsWithoutPricetag') && !$price) {
                continue;
            }

            $shippingOffered = $post->find('.css-1c0ed4l svg', 0)->outertext;
            if ($this->getInput('shippingOfferedOnly') && !$shippingOffered) {
                continue;
            }

            $negotiable = $post->find('p[data-testid="ad-price"] span.css-e2218f', 0)->plaintext;
            if ($negotiable) {
                $price = trim(str_replace($negotiable, '', $price));
                $negotiable = '(' . $negotiable . ')';
            }

            if ($post->find('h6', 0)->plaintext != '') {
                $item['uri'] = $post->find('a', 0)->href;
                $item['title'] = $post->find('h6', 0)->plaintext;
            }

            $img = $post->find('img', 0)->src;
            # Once we hit the lazy-loading images, we need to deep-crawl
            if (pathinfo($img, PATHINFO_EXTENSION) == 'svg') {
                $articleHTMLContent = getSimpleHTMLDOMCached($item['uri']);

                if ($articleHTMLContent) {
                    $img = $articleHTMLContent->find('div.swiper-wrapper img', 0)->src;
                }
            }

            $locationAndDate = $post->find('p[data-testid="location-date"]', 0)->plaintext;
            $locationAndDateArray = explode(' - ', $locationAndDate, 2);
            $location = trim($locationAndDateArray[0]);
            $date = trim($locationAndDateArray[1]);

            $item['content'] = <<<CONTENT
<table>
    <tbody>
      <tr>
        <td style="width=300;">
              <p><img src="$img"></p>
        </td>
       </tr>
       <tr>
        <td>
          <p>$location</p>
          <p>$date</p>
          <p><span style="font-weight:bold">$price</span> $negotiable <span>$shippingOffered</span></p>
        </td>
      </tr>
    </tbody>
</table>
CONTENT;

            $this->items[] = $item;
        }
    }
}
