<?php

// CVE Details is a collection of CVEs, taken from the National Vulnerability
// Database (NVD) and other sources like the Exploit DB and Metasploit. The
// website categorizes it by vendor and product and attach the CWE category.
// There is a Atom feed available, but only logged in users can use it,
// it is not reliable and contain no useful information. This bridge create a
// sane feed with additional information like tags and a link to the CWE
// a description of the vulnerability.
class CVEDetailsBridge extends BridgeAbstract
{
    const MAINTAINER = 'Aaron Fischer';
    const NAME = 'CVE Details';
    const CACHE_TIMEOUT = 60 * 60 * 6; // 6 hours
    const DESCRIPTION = 'Report new CVE vulnerabilities for a given vendor (and product)';
    const URI = 'https://www.cvedetails.com';

    const PARAMETERS = [[
        // The Vendor ID can be taken from the URL
        'vendor_id' => [
            'name' => 'Vendor ID',
            'type' => 'number',
            'required' => true,
            'exampleValue' => 74, // PHP
        ],
        // The optional Product ID can be taken from the URL as well
        'product_id' => [
            'name' => 'Product ID',
            'type' => 'number',
            'required' => false,
            'exampleValue' => 128, // PHP
        ],
    ]];

    private $html = null;
    private $vendor = '';
    private $product = '';

    public function collectData()
    {
        if ($this->html == null) {
            $this->fetchContent();
        }

        $var = $this->html->find('#searchresults > div > div.row');
        foreach ($var as $i => $tr) {
            $uri = $tr->find('h3 > a', 0)->href ?? null;
            $title = $tr->find('h3 > a', 0)->innertext;
            $content = $tr->find('.cvesummarylong', 0)->innertext ?? '';
            $timestamp = $tr->find('[data-tsvfield="publishDate"]', 0)->innertext ?? 0;

            $this->items[] = [
                'uri'           => $uri,
                'title'         => $title,
                'timestamp'     => $timestamp,
                'content'       => $content,
                'categories'    => [$this->vendor],
                'enclosures'    => [],
                'uid'           => $title,
            ];
            if (count($this->items) >= 30) {
                break;
            }
        }
    }

    // Make the actual request to cvedetails.com and stores the response
    // (HTML) for later use and extract vendor and product from it.
    private function fetchContent()
    {
        // build url
        // Return the URL to query.
        // Because of the optional product ID, we need to attach it if it is
        // set. The search result page has the exact same structure (with and
        // without the product ID).
        $url = self::URI . '/vulnerability-list/vendor_id-' . $this->getInput('vendor_id');
        if ($this->getInput('product_id') !== '') {
            $url .= '/product_id-' . $this->getInput('product_id');
        }
        // Sadly, there is no way (prove me wrong please) to sort the search
        // result by publish date. So the nearest alternative is the CVE
        // number, which should be mostly accurate.
        $url .= '?order=1'; // Order by CVE number DESC

        $html = getSimpleHTMLDOM($url);
        $this->html = defaultLinkTo($html, self::URI);

        $vendor = $html->find('#contentdiv h1 > a', 0);
        if ($vendor == null) {
            throwServerException('Invalid Vendor ID ' . $this->getInput('vendor_id') . ' or Product ID ' . $this->getInput('product_id'));
        }
        $this->vendor = $vendor->innertext;

        $product = $html->find('#contentdiv h1 > a', 1);
        if ($product != null) {
            $this->product = $product->innertext;
        }
    }

    public function getName()
    {
        if ($this->getInput('vendor_id') == '') {
            return self::NAME;
        }

        if ($this->html == null) {
            $this->fetchContent();
        }

        $name = 'CVE Vulnerabilities for ' . $this->vendor;
        if ($this->product != '') {
            $name .= '/' . $this->product;
        }

        return $name;
    }
}
