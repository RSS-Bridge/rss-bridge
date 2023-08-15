<?php

class SitemapBridge extends CssSelectorBridge
{
    const MAINTAINER = 'ORelio';
    const NAME = 'Sitemap Bridge';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge/';
    const DESCRIPTION = 'Convert any site to RSS feed using SEO Sitemap and CSS selectors (Advanced Users)';
    const PARAMETERS = [
        [
            'home_page' => [
                'name' => 'Site URL: Home page with latest articles',
                'title' => <<<EOT
                    The bridge will analyze the site like a search engine does.
                    The URL specified here determines the feed title and URL.
                    EOT,
                'exampleValue' => 'https://example.com/blog/',
                'required' => true
            ],
            'url_pattern' => [
                'name' => 'Pattern for site URLs to take in feed',
                'title' => 'Select items by applying a regular expression on their URL',
                'exampleValue' => 'https://example.com/article/.*',
                'required' => true
            ],
            'content_selector' => [
                'name' => 'Selector for each article content',
                'title' => <<<EOT
                    This bridge works using CSS selectors, e.g. "div.article" will match <div class="article">.
                    Everything inside that element becomes feed item content.
                    EOT,
                'exampleValue' => 'article.content',
                'required' => true
            ],
            'content_cleanup' => [
                'name' => '[Optional] Content cleanup: List of items to remove',
                'title' => 'Selector for unnecessary elements to remove inside article contents.',
                'exampleValue' => 'div.ads, div.comments',
            ],
            'title_cleanup' => [
                'name' => '[Optional] Text to remove from article title',
                'title' => 'Specify here some text from page title that need to be removed, e.g. " | BlogName".',
                'exampleValue' => ' | BlogName',
            ],
            'site_map' => [
                'name' => '[Optional] sitemap.xml URL',
                'title' => <<<EOT
                    By default, the bridge will analyze robots.txt to find out URL for sitemap.xml.
                    Alternatively, you can specify here the direct URL for sitemap XML.
                    The sitemap.xml file must have <loc> and <lastmod> fields for the bridge to work:
                    Eg. <url><loc>https://article/url</loc><lastmod>2000-12-31T23:59Z</lastmod></url>
                    <loc> is feed item URL, <lastmod> for selecting the most recent entries.
                    EOT,
                'exampleValue' => 'https://example.com/sitemap.xml',
            ],
            'limit' => self::LIMIT
        ]
    ];

    public function collectData()
    {
        $url = $this->getInput('home_page');
        $url_pattern = $this->getInput('url_pattern');
        $content_selector = $this->getInput('content_selector');
        $content_cleanup = $this->getInput('content_cleanup');
        $title_cleanup = $this->getInput('title_cleanup');
        $site_map = $this->getInput('site_map');
        $limit = $this->getInput('limit');

        $this->feedName = $this->getPageTitle($url, $title_cleanup);
        $sitemap_url = empty($site_map) ? $url : $site_map;
        $sitemap_xml = $this->getSitemapXml($sitemap_url, !empty($site_map));
        $links = $this->sitemapXmlToList($sitemap_xml, $url_pattern, empty($limit) ? 10 : $limit);

        if (empty($links) && empty(sitemapXmlToList($sitemap_xml))) {
            returnClientError('Could not retrieve URLs with Timestamps from Sitemap: ' . $sitemap_url);
        }

        foreach ($links as $link) {
            $this->items[] = $this->expandEntryWithSelector($link, $content_selector, $content_cleanup, $title_cleanup);
        }
    }

    /**
     * Retrieve site map from specified URL
     * @param string $url URL pointing to any page of the site, e.g. "https://example.com/blog" OR directly to the site map e.g. "https://example.com/sitemap.xml"
     * @param string $is_site_map TRUE if the specified URL points directly to the sitemap XML
     * @return object Sitemap DOM (from parsed XML)
     */
    protected function getSitemapXml(&$url, $is_site_map = false)
    {
        if (!$is_site_map) {
            $robots_txt = getSimpleHTMLDOM(urljoin($url, '/robots.txt'))->outertext;
            preg_match('/Sitemap: ([^ ]+)/', $robots_txt, $matches);
            if (empty($matches)) {
                returnClientError('Failed to determine Sitemap from robots.txt. Try setting it manually.');
            }
            $url = $matches[1];
        }
        return getSimpleHTMLDOM($url);
    }

    /**
     * Retrieve N most recent URLs from Site Map
     * @param object $sitemap Site map XML DOM
     * @param string $url_pattern Optional pattern to look for in URLs
     * @param int $limit Optional maximum amount of URLs to return
     * @param bool $keep_date TRUE to keep dates (url => date array instead of url array)
     * @return array Array of URLs
     */
    protected function sitemapXmlToList($sitemap, $url_pattern = '', $limit = 0, $keep_date = false)
    {
        $links = [];

        foreach ($sitemap->find('sitemap') as $nested_sitemap) {
            $url = $nested_sitemap->find('loc');
            if (!empty($url)) {
                $url = $url[0]->plaintext;
                if (str_ends_with(strtolower($url), '.xml')) {
                    $nested_sitemap_xml = $this->getSitemapXml($url, true);
                    $nested_sitemap_links = $this->sitemapXmlToList($nested_sitemap_xml, $url_pattern, null, true);
                    $links = array_merge($links, $nested_sitemap_links);
                }
            }
        }

        if (!empty($url_pattern)) {
            $url_pattern = str_replace('/', '\/', $url_pattern);
        }

        foreach ($sitemap->find('url') as $item) {
            $url = $item->find('loc');
            $lastmod = $item->find('lastmod');
            if (!empty($url) && !empty($lastmod)) {
                $url = $url[0]->plaintext;
                $lastmod = $lastmod[0]->plaintext;
                $timestamp = strtotime($lastmod);
                if (empty($url_pattern) || preg_match('/' . $url_pattern . '/', $url) === 1) {
                    $links[$url] = $timestamp;
                }
            }
        }

        arsort($links);

        if ($limit > 0 && count($links) > $limit) {
            $links = array_slice($links, 0, $limit);
        }

        return $keep_date ? $links : array_keys($links);
    }
}
