<?php

class OLXBridge extends BridgeAbstract
{
    const NAME = 'OLX';
    const DESCRIPTION = <<<'EOF'
Returns the search results from the OLX auctioning platforms
(Bulgaria, Kazakhstan, Poland, Portugal, Romania, Ukraine and Uzbekistan only)
EOF;

    const URI = 'https://www.olx.com';
    const MAINTAINER = 'wrobelda';
    const PARAMETERS = [[
        'url' => [
            'name' => 'Search URL',
            'title' => 'Copy the URL from your browser\'s address bar after searching for your items and paste it here',
            'pattern' => '^(https:\/\/)?(www.)?olx\.(bg|kz|pl|pt|ro|ua|uz).*$',
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

    private function getHostname()
    {
        $scheme = parse_url($this->getInput('url'), PHP_URL_SCHEME);
        $host = parse_url($this->getInput('url'), PHP_URL_HOST);

        return $scheme . '://' . $host;
    }

    public function getURI()
    {
        if ($this->getInput('url')) {
            # make sure we order by the most recently listed offers
            $uri = trim(preg_replace('/([?&])search%5Border%5D=[^&]+(&|$)/', '$1', $this->getInput('url')), '?&/');
            $uri = preg_replace('/([?&])view=[^&]+(&|$)/', '', $uri);
            $uri .= (parse_url($uri, PHP_URL_QUERY) ? '&' : '?') . 'search%5Border%5D=created_at:desc';

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

        $parsedUrl = Url::fromString($url);
        $paths = explode('/', $parsedUrl->getPath());

        $query = array_reduce($paths, function ($q, $p) {
            if (preg_match('/^q-(.+)$/i', $p, $matches)) {
                $q[] = str_replace('-', ' ', urldecode($matches[1]));
            }

            return $q;
        });

        if ($query) {
            return $query[0];
        }

        return parent::getName();
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $html = defaultLinkTo($html, $this->getHostname());

        $isoLang = $html->find('meta[http-equiv=Content-Language]', 0)->content;

        # the second grid, if any, has extended results from additional categories, outside of original search scope
        $listing_grid = $html->find("div[data-testid='listing-grid']", 0);

        $results = $listing_grid->find("div[data-cy='l-card']");

        foreach ($results as $post) {
            $item = [];

            if (!$this->getInput('includeFeaturedPosts') && $post->find('div[data-testid="adCard-featured"]', 0)) {
                continue;
            }

            $price = $post->find('p[data-testid="ad-price"]', 0)->plaintext ?? '';
            if (!$this->getInput('includePostsWithoutPricetag') && !$price) {
                continue;
            }

            $negotiable = $post->find('p[data-testid="ad-price"] span.css-e2218f', 0)->plaintext ?? false;
            if ($negotiable) {
                $price = trim(str_replace($negotiable, '', $price));
                $negotiable = '(' . $negotiable . ')';
            }

            if ($post->find('h4', 0)->plaintext != '') {
                $item['uri'] = $post->find('a', 0)->href;
                $item['title'] = $post->find('h4', 0)->plaintext;
            }

            # ignore the date component, as it is too convoluted — use the deep-crawled one; see below
            $locationAndDate = $post->find('p[data-testid="location-date"]', 0)->plaintext;
            $locationAndDateArray = explode(' - ', $locationAndDate, 2);
            $location = trim($locationAndDateArray[0]);

            # OLX only shows 5 results before images get lazy-loaded, so we have to deep-crawl *almost* all the results.
            # Given that, do deep-crawl *all* the results, which allows to aso obtain the ID, the simplified location
            # and date strings, as well as the detailed description.
            $articleHTMLContent = getSimpleHTMLDOMCached($item['uri']);
            $articleHTMLContent = defaultLinkTo($articleHTMLContent, $this->getHostname());

            $shippingOffered = $articleHTMLContent->find('img[alt="Safety Badge"]', 0)->src ?? false;
            if ($this->getInput('shippingOfferedOnly') && !$shippingOffered) {
                continue;
            }

            # Extract a clean ID without resorting to the convoluted CSS class or sibling selectors. Should be always present.
            $refreshLink = $articleHTMLContent->find('a[data-testid=refresh-link]', 0)->href ?? false;
            if ($refreshLink) {
                parse_str(parse_url($refreshLink, PHP_URL_QUERY), $refreshQuery);
                $item['uid'] = $refreshQuery['ad-id'];
            } else {
                # may be an imported offer from a sibling auto-moto classifieds platform
                $item['uid'] = $articleHTMLContent->find('span[id=ad_id]', 0)->plaintext;
            }

            $img = $articleHTMLContent->find('meta[property="og:image"]', 0)->content ?? false;
            if ($img) {
                $item['enclosures'] = [$img . '#.image'];
            }

            $isoDate = $articleHTMLContent->find('meta[property="og:updated_time"]', 0)->content ?? false;
            if ($isoDate) {
                $item['timestamp'] = strtotime($isoDate);
            } else {
                $date = $articleHTMLContent->find('span[data-cy="ad-posted-at"]', 0)->plaintext;
                # Relative, today
                if (preg_match('/^.*\s(\d\d:\d\d)$/i', $date, $matches)) {
                    $item['timestamp'] = strtotime($matches[1]);
                } else {
                    # full, localized date
                    $formatter = new IntlDateFormatter($isoLang, IntlDateFormatter::SHORT, IntlDateFormatter::NONE);
                    $item['timestamp'] = $formatter->parse($date);
                }
            }

            $descriptionHtml = $articleHTMLContent->find('div[data-cy="ad_description"] div', 0)->innertext ?? false;
            if (!$descriptionHtml) {
                $descriptionHtml = $articleHTMLContent->find('div[id="description"] div[data-read-more]', 0)->innertext ?? false;
            }

            $item['categories'] = [];
            $breadcrumbs = $articleHTMLContent->find('li[data-testid="breadcrumb-item"]');
            foreach ($breadcrumbs as $breadcrumb) {
                $category = $breadcrumb->find('a[href!="/"]', 0) ?? false;

                if ($category) {
                    $item['categories'][] = $category->plaintext;
                }
            }

            $parameters = $articleHTMLContent->find('div.parametersArea li');
            foreach ($parameters as $parameter) {
                $category = $parameter->find('a', 0)->plaintext ?? false;

                if ($category = empty($category) ? false : trim($category)) {
                    if ($category == 'Tak') {
                        $category = $parameter->find('span', 0)->plaintext ?? '';
                    } elseif ($category == 'Nie') {
                        continue;
                    }

                    $item['categories'][] = $category;
                }
            }

            $item['content'] = <<<CONTENT
<table>
    <tbody>
      <tr>
        <td>
          <p>$location</p>
          <p><span style="font-weight:bold">$price</span> $negotiable <span><img src="$shippingOffered"</img></span></p>
        </td>
      </tr>
      <tr>
        <td>$descriptionHtml</td>
      </tr>
    </tbody>
</table>
CONTENT;
            $this->items[] = $item;
        }
    }
}
