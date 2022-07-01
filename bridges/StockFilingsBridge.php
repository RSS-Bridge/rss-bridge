<?php

class StockFilingsBridge extends FeedExpander
{
    const MAINTAINER = 'captn3m0';
    const NAME = 'SEC Stock filings';
    const URI = 'https://www.sec.gov/edgar/searchedgar/companysearch.html';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Tracks SEC Filings for a single company';
    const SEARCH_URL = 'https://www.sec.gov/cgi-bin/browse-edgar?owner=exclude&action=getcompany&CIK=';
    const WEBSITE_ROOT = 'https://www.sec.gov';

    const PARAMETERS = [
        [
        'ticker' => [
            'name'          => 'cik',
            'required'      => true,
            'exampleValue'  => 'AMD',
            // https://stackoverflow.com/a/12827734
            'pattern'       => '[A-Za-z0-9]+',
        ],
        ]];

    public function getIcon()
    {
        return 'https://www.sec.gov/favicon.ico';
    }

    /**
     * Generates search URL
     */
    private function getSearchUrl()
    {
        return self::SEARCH_URL . $this->getInput('ticker');
    }

    /**
     * Returns the Company Name
     */
    private function getRssFeed($html)
    {
        $links = $html->find('#contentDiv a');

        foreach ($links as $link) {
            $href = $link->href;

            if (substr($href, 0, 4) !== 'http') {
                $href = self::WEBSITE_ROOT . $href;
            }
            parse_str(html_entity_decode(parse_url($href, PHP_URL_QUERY)), $query);

            if (isset($query['output']) and ($query['output'] == 'atom')) {
                return $href;
            }
        }

        return false;
    }

    /**
     * Return \simple_html_dom object
     * for the entire html of the product page
     */
    private function getHtml()
    {
        $uri = $this->getSearchUrl();

        return getSimpleHTMLDOM($uri) ?: returnServerError('Could not request SEC.');
    }

    /**
     * Scrape the SEC Stock Filings RSS Feed URL
     * and redirect there
     */
    public function collectData()
    {
        $html = $this->getHtml();
        $rssFeedUrl = $this->getRssFeed($html);

        if ($rssFeedUrl) {
            parent::collectExpandableDatas($rssFeedUrl);
        } else {
            returnClientError('Could not find RSS Feed URL. Are you sure you used a valid CIK?');
        }
    }
}
