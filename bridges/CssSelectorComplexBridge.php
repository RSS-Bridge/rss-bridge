<?php

class CssSelectorComplexBridge extends BridgeAbstract
{
    const MAINTAINER = 'Lars Stegman';
    const NAME = 'CSS Selector Complex Bridge';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge/';
    const DESCRIPTION = <<<EOT
        Convert any site to RSS feed using CSS selectors (Advanced Users). The bridge first selects 
        the element describing the article entries. It then extracts the links to the articles from 
        these elements. It then, depending on the setting "Load article from page", either parses 
        the selected elements, or downloads the page for each article and parses those. Parsing the 
        elements or page is done using the provided selectors.
        EOT;
    const PARAMETERS = [
        [
            'home_page' => [
                'name' => 'Site URL: Page with latest articles',
                'exampleValue' => 'https://example.com/blog/',
                'required' => true
            ],
            'cookie' => [
                'name' => '[Optional] Cookie',
                'title' => <<<EOT
                Use when the website does not send the page contents, unless a static cookie is included.
                EOT,
                'exampleValue' => 'sessionId=deadb33f'
            ],
            'title_cleanup' => [
                'name' => '[Optional] Text to remove from feed title',
                'title' => <<<EOT
                Text to remove from the feed title, which is read from the article list page.
                EOT,
                'exampleValue' => ' | BlogName',
            ],
            'entry_element_selector' => [
                'name' => 'Selector for article entry elements',
                'title' => <<<EOT
                This bridge works using CSS selectors, e.g. "div.article" will match all 
                <div class="article">...</div> on home page, each one being treated as a feed item.

                Use the URL selector option to select the `a` element with the
                `href` to the article link. If this option is not configured, the first encountered 
                `a` element is used.
                EOT,
                'exampleValue' => 'div.article',
                'required' => true
            ],
            'url_selector' => [
                'name' => '[Optional] Selector for link elements',
                'title' => <<<EOT
                    The selector to find `a` elements in the entry element. If empty,
                    the first encountered `a` element is used. The `href` property
                    is used to create entries in the feed.
                    EOT,
                'exampleValue' => 'a.article',
                'defaultValue' => 'a'
            ],
            'url_pattern' => [
                'name' => '[Optional] Pattern for site URLs to keep in feed',
                'title' => 'Optionally filter items by applying a regular expression on their URL',
                'exampleValue' => '/blog/article/.*',
            ],
            'limit' => self::LIMIT,
            'use_article_pages' => [
                'name' => 'Load article from page',
                'title' => <<<EOT
                If true, the article page is load and parsed to get the article contents using 
                the css selectors. (Slower!)
                Otherwise, the element selected by the article entry selector is used.
                EOT,
                'type' => 'checkbox'
            ],
            'article_page_content_selector' => [
                'name' => '[Optional] Selector to select article element',
                'title' => 'Extract the article from its page using the provided selector',
                'exampleValue' => 'article.content',
            ],
            'content_cleanup' => [
                'name' => '[Optional] Content cleanup: selector for items to remove',
                'title' => 'Selector for unnecessary elements to remove inside article contents.',
                'exampleValue' => 'div.ads, div.comments',
            ],
            'title_selector' => [
                'name' => '[Optional] Selector for the article title',
                'title' => 'Selector to select the article title',
                'defaultValue' => 'h1'
            ],
            'category_selector' => [
                'name' => '[Optional] Categories',
                'title' => <<<EOT
                Selector to extract the catgories the article has
                EOT,
                'exampleValue' => 'span.category, #main-category'
            ],
            'author_selector' => [
                'name' => '[Optional] Author',
                'title' => <<<EOT
                Selector to extract the author of the article. If multiple elements are selected
                the first one is used.
                EOT,
                'exampleValue' => 'span#author'
            ],
            'time_selector' => [
                'name' => '[Optional] Time selector',
                'title' => <<<EOT
                Selector to extract the timestamp of the article. If the element 
                is an html5 `time` element, the value for the `datetime` attribute is used.
                EOT,
            ],
            'time_format' => [
                'name' => '[Optional] Format string for parsing time',
                'title' => <<<EOT
                The format to use to parse the timestamp. See 
                https://www.php.net/manual/en/datetimeimmutable.createfromformat.php
                for the format specification.
                EOT
            ],
            'remove_styling' => [
                'name' => '[Optional] Remove styling',
                'title' => 'Remove class and style attributes from the page elements',
                'type' => 'checkbox'
            ]
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

    protected function getHeaders()
    {
        $headers = [];
        $cookie = $this->getInput('cookie');
        if (!empty($cookie)) {
            $headers[] = 'Cookie: ' . $cookie;
        }

        return $headers;
    }

    public function collectData()
    {
        $url = $this->getInput('home_page');
        $headers = $this->getHeaders();

        $entry_element_selector = $this->getInput('entry_element_selector');
        $url_selector = $this->getInput('url_selector');
        $url_pattern = $this->getInput('url_pattern');
        $limit = $this->getInput('limit') ?? 10;

        $use_article_pages = $this->getInput('use_article_pages');
        $article_page_content_selector = $this->getInput('article_page_content_selector');
        $content_cleanup = $this->getInput('content_cleanup');
        $title_selector = $this->getInput('title_selector');
        $title_cleanup = $this->getInput('title_cleanup');
        $time_selector = $this->getInput('time_selector');
        $time_format = $this->getInput('time_format');

        $category_selector = $this->getInput('category_selector');
        $author_selector = $this->getInput('author_selector');
        $remove_styling = $this->getInput('remove_styling');

        $html = defaultLinkTo(getSimpleHTMLDOM($url, $headers), $url);
        $this->feedName = $this->getTitle($html, $title_cleanup);
        $entry_elements = $this->htmlFindEntryElements($html, $entry_element_selector, $url_selector, $url_pattern, $limit);

        if (empty($entry_elements)) {
            return;
        }

        // Fetch the elements from the article pages.
        if ($use_article_pages) {
            if (empty($article_page_content_selector)) {
                returnClientError('`Article selector` is required when `Load article page` is enabled');
            }

            foreach (array_keys($entry_elements) as $uri) {
                $entry_elements[$uri] = $this->fetchArticleElementFromPage($uri, $article_page_content_selector);
            }
        }

        foreach ($entry_elements as $uri => $element) {
            $entry = $this->parseEntryElement(
                $element,
                $title_selector,
                $author_selector,
                $category_selector,
                $time_selector,
                $time_format,
                $content_cleanup,
                $this->feedName,
                $remove_styling
            );

            $entry['uri'] = $uri;
            $this->items[] = $entry;
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
    protected function getTitle($page, $title_cleanup)
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
     * Remove all elements from HTML content matching cleanup selector
     * @param string|object $content HTML content as HTML object or string
     * @return string|object Cleaned content (same type as input)
     */
    protected function cleanArticleContent($content, $cleanup_selector, $remove_styling)
    {
        $string_convert = false;
        if (is_string($content)) {
            $string_convert = true;
            $content = str_get_html($content);
        }

        if (!empty($cleanup_selector)) {
            foreach ($content->find($cleanup_selector) as $item_to_clean) {
                $item_to_clean->outertext = '';
            }
        }

        if ($remove_styling) {
            foreach (['class', 'style'] as $attribute_to_remove) {
                foreach ($content->find('[' . $attribute_to_remove . ']') as $item_to_clean) {
                    $item_to_clean->removeAttribute($attribute_to_remove);
                }
            }
        }

        if ($string_convert) {
            $content = $content->outertext;
        }
        return $content;
    }


    /**
     * Retrieve first N link+element from webpage URL or DOM satisfying the specified criteria
     * @param string|object $page URL or DOM to retrieve feed items from
     * @param string $entry_selector DOM selector for matching HTML elements that contain article
     *  entries
     * @param string $url_selector DOM selector for matching links
     * @param string $url_pattern Optional filter to keep only links matching the pattern
     * @param int $limit Optional maximum amount of URLs to return
     * @return array of items { <uri> => <html-element> }
     */
    protected function htmlFindEntryElements($page, $entry_selector, $url_selector, $url_pattern = '', $limit = 0)
    {
        if (is_string($page)) {
            $page = getSimpleHTMLDOM($page);
        }

        $entryElements = $page->find($entry_selector);
        if (empty($entryElements)) {
            returnClientError('No entry elements for entry selector');
        }

        // Extract URIs with the associated entry element
        $links_with_elements = [];
        foreach ($entryElements as $entry) {
            $url_element = $entry->find($url_selector, 0);
            if (is_null($url_element)) {
                // No `a` element found in this entry
                if ($entry->tag == 'a') {
                    $url_element = $entry;
                } else {
                    continue;
                }
            }

            $links_with_elements[$url_element->href] = $entry;
        }

        if (empty($links_with_elements)) {
            returnClientError('The provided URL selector matches some elements, but they do not 
                contain links.');
        }

        // Filter using the URL pattern
        $filtered_urls = $this->filterUrlList(array_keys($links_with_elements), $url_pattern, $limit);

        if (empty($filtered_urls)) {
            returnClientError('No results for URL pattern');
        }

        $items = [];
        foreach ($filtered_urls as $link) {
            $items[$link] = $links_with_elements[$link];
        }

        return $items;
    }


    /**
     * Retrieve article element from its URL using content selector and return the DOM element
     * @param string $entry_url URL to retrieve article from
     * @param string $content_selector HTML selector for extracting content, e.g. "article.content"
     * @return article DOM element
     */
    protected function fetchArticleElementFromPage($entry_url, $content_selector)
    {
        $entry_html = getSimpleHTMLDOMCached($entry_url);
        $article_content = $entry_html->find($content_selector, 0);

        if (is_null($article_content)) {
            returnClientError('Could not article content at URL: ' . $entry_url);
        }

        $article_content = defaultLinkTo($article_content, $entry_url);
        return $article_content;
    }

    protected function parseTimeStrAsTimestamp($timeStr, $format)
    {
        $date = date_parse_from_format($format, $timeStr);
        if ($date['error_count'] != 0) {
            returnClientError('Error while parsing time string');
        }

        $timestamp = mktime(
            $date['hour'],
            $date['minute'],
            $date['second'],
            $date['month'],
            $date['day'],
            $date['year']
        );

        if ($timestamp == false) {
            returnClientError('Error while creating timestamp');
        }

        return $timestamp;
    }

    /**
     * Retrieve article content from its URL using content selector and return a feed item
     * @param object $entry_html A DOM element containing the article
     * @param string $title_selector A selector to the article title from the article
     * @param string $author_selector A selector to find the article author
     * @param string $time_selector A selector to get the article publication time.
     * @param string $time_format The format to parse the time_selector.
     * @param string $content_cleanup Optional selector for removing elements, e.g. "div.ads,
     *  div.comments"
     * @param string $title_default Optional title to use when could not extract title reliably
     * @param bool $remove_styling Whether to remove class and style attributes from the HTML
     * @return array Entry data: uri, title, content
     */
    protected function parseEntryElement(
        $entry_html,
        $title_selector = null,
        $author_selector = null,
        $category_selector = null,
        $time_selector = null,
        $time_format = null,
        $content_cleanup = null,
        $title_default = null,
        $remove_styling = false
    ) {
        $article_content = convertLazyLoading($entry_html);

        if (is_null($title_selector)) {
            $article_title = $title_default;
        } else {
            $article_title = trim($entry_html->find($title_selector, 0)->innertext);
        }

        $author = null;
        if (!is_null($author_selector) && $author_selector != '') {
            $author = trim($entry_html->find($author_selector, 0)->innertext);
        }

        $categories = [];
        if (!is_null($category_selector && $category_selector != '')) {
            $category_elements = $entry_html->find($category_selector);
            foreach ($category_elements as $category_element) {
                $categories[] = trim($category_element->innertext);
            }
        }

        $time = null;
        if (!is_null($time_selector) && $time_selector != '') {
            $time_element = $entry_html->find($time_selector, 0);
            $time = $time_element->getAttribute('datetime');
            if (is_null($time)) {
                $time = $time_element->innertext;
            }

            $this->parseTimeStrAsTimestamp($time, $time_format);
        }

        $article_content = $this->cleanArticleContent($article_content, $content_cleanup, $remove_styling);

        $item = [];
        $item['title'] = $article_title;
        $item['content'] = $article_content;
        $item['categories'] = $categories;
        $item['timestamp'] = $time;
        $item['author'] = $author;
        return $item;
    }
}
