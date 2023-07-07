<?php

class CssSelectorBridge extends BridgeAbstract
{
    const MAINTAINER = 'ORelio';
    const NAME = 'CSS Selector Bridge';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge/';
    const DESCRIPTION = 'Convert any site to RSS feed using CSS selectors (Advanced Users)';
    const PARAMETERS = [
        [
            'home_page' => [
                'name' => 'Site URL: Home page with latest articles',
                'exampleValue' => 'https://example.com/blog/',
                'required' => true
            ],
            'url_selector' => [
                'name' => 'Selector for article links or their parent elements',
                'exampleValue' => 'a.article',
                'required' => true
            ],
            'url_pattern' => [
                'name' => '[Optional] Pattern for site URLs to keep in feed',
                'exampleValue' => 'https://example.com/article/.*',
            ],
            'content_selector' => [
                'name' => '[Optional] Selector to extract each article content',
                'exampleValue' => 'article.content',
            ],
            'content_cleanup' => [
                'name' => '[Optional] Content cleanup: List of items to remove',
                'exampleValue' => 'div.ads, div.comments',
            ],
            'title_cleanup' => [
                'name' => '[Optional] Text to remove from expanded article title',
                'exampleValue' => ' | BlogName',
            ],
            'limit' => self::LIMIT
        ]
    ];

    private $feedName = '';

    public function getURI()
    {
        $url = $this->getInput('home_page');
        if (empty($url)) {
            $url = parent::getURI();
        }
        return $url;
    }

    public function getName()
    {
        if (!empty($this->feedName)) {
            return $this->feedName;
        }
        return parent::getName();
    }

    public function collectData()
    {
        $url = $this->getInput('home_page');
        $url_selector = $this->getInput('url_selector');
        $url_pattern = $this->getInput('url_pattern');
        $content_selector = $this->getInput('content_selector');
        $content_cleanup = $this->getInput('content_cleanup');
        $title_cleanup = $this->getInput('title_cleanup');
        $limit = $this->getInput('limit') ?? 10;

        $html = defaultLinkTo(getSimpleHTMLDOM($url), $url);
        $this->feedName = $this->getPageTitle($html, $title_cleanup);
        $items = $this->htmlFindLinks($html, $url_selector, $url_pattern, $limit);

        if (empty($content_selector)) {
            $this->items = $items;
        } else {
            foreach ($items as $item) {
                $this->items[] = $this->expandEntryWithSelector(
                    $item['uri'],
                    $content_selector,
                    $content_cleanup,
                    $title_cleanup
                );
            }
        }
    }

    /**
     * Filter a list of URLs using a pattern and limit
     * @param array $links List of URLs
     * @param string $url_pattern Pattern to look for in URLs
     * @param int $limit Optional maximum amount of URLs to return
     * @return array Array of URLs
     */
    protected function filterUrlList($links, $url_pattern, $limit = 0)
    {
        if (!empty($url_pattern)) {
            $url_pattern = '/' . str_replace('/', '\/', $url_pattern) . '/';
            $links = array_filter($links, function ($url) {
                return preg_match($url_pattern, $url) === 1;
            });
        }

        if ($limit > 0 && count($links) > $limit) {
            $links = array_slice($links, 0, $limit);
        }

        return $links;
    }

    /**
     * Retrieve title from webpage URL or DOM
     * @param string|object $page URL or DOM to retrieve title from
     * @param string $title_cleanup optional string to remove from webpage title, e.g. " | BlogName"
     * @return string Webpage title
     */
    protected function getPageTitle($page, $title_cleanup = null)
    {
        if (is_string($page)) {
            $page = getSimpleHTMLDOMCached($page);
        }
        $title = html_entity_decode($page->find('title', 0)->plaintext);
        if (!empty($title)) {
            $title = trim(str_replace($title_cleanup, '', $title));
        }
        return $title;
    }

    /**
     * Retrieve first N links from webpage URL or DOM satisfying the specified criteria
     * @param string|object $page URL or DOM to retrieve links from
     * @param string $url_selector DOM selector for matching links or their parent element
     * @param string $url_pattern Optional filter to keep only links matching the pattern
     * @param int $limit Optional maximum amount of URLs to return
     * @return array of minimal feed items {'uri': entry_url, 'title', entry_title}
     */
    protected function htmlFindLinks($page, $url_selector, $url_pattern = '', $limit = 0)
    {
        $links = $page->find($url_selector);

        if (empty($links)) {
            returnClientError('No results for URL selector');
        }

        $link_to_title = [];
        foreach ($links as $link) {
            if ($link->tag != 'a') {
                $link = $link->find('a', 0);
            }
            $link_to_title[$link->href] = $link->plaintext;
        }

        $links = $this->filterUrlList(array_keys($link_to_title), $url_pattern, $limit);

        if (empty($links)) {
            returnClientError('No results for URL pattern');
        }

        $items = [];
        foreach ($links as $link) {
            $item = [];
            $item['uri'] = $link;
            $item['title'] = $link_to_title[$link];
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Retrieve article content from its URL using content selector and return a feed item
     * @param string $entry_url URL to retrieve article from
     * @param string $content_selector HTML selector for extracting content, e.g. "article.content"
     * @param string $content_cleanup Optional selector for removing elements, e.g. "div.ads, div.comments"
     * @param string $title_cleanup Optional string to remove from article title, e.g. " | BlogName"
     * @return array Entry data: uri, title, content
     */
    protected function expandEntryWithSelector($entry_url, $content_selector, $content_cleanup = null, $title_cleanup = null)
    {
        if (empty($content_selector)) {
            returnClientError('Please specify a content selector');
        }

        $entry_html = getSimpleHTMLDOMCached($entry_url);
        $article_content = $entry_html->find($content_selector);

        if (!empty($article_content)) {
            $article_content = $article_content[0];
        } else {
            returnClientError('Could not find content selector at URL: ' . $entry_url);
        }

        if (!empty($content_cleanup)) {
            foreach ($article_content->find($content_cleanup) as $item_to_clean) {
                $item_to_clean->outertext = '';
            }
        }

        $article_content = convertLazyLoading($article_content);
        $article_content = defaultLinkTo($article_content, $entry_url);

        $item = [];
        $item['uri'] = $entry_url;
        $item['title'] = $this->getPageTitle($entry_html, $title_cleanup);
        $item['content'] = $article_content;
        return $item;
    }
}
