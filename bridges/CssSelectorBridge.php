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
                'title' => <<<EOT
                    This bridge works using CSS selectors, e.g. "a.article" will match all <a class="article" 
                    href="URL">TITLE</a> on home page, each one being treated as a feed item. &#10;&#13;
                    Instead of just a link you can selet one of its parent element. Everything inside that
                    element becomes feed item content, e.g. image and summary present on home page.
                    When doing so, the first link inside the selected element becomes feed item URL/Title.
                    EOT,
                'exampleValue' => 'a.article',
                'required' => true
            ],
            'url_pattern' => [
                'name' => '[Optional] Pattern for site URLs to keep in feed',
                'title' => 'Optionally filter items by applying a regular expression on their URL',
                'exampleValue' => '/blog/article/.*',
            ],
            'content_selector' => [
                'name' => '[Optional] Selector to expand each article content',
                'title' => <<<EOT
                    When specified, the bridge will fetch each article from its URL
                    and extract content using the provided selector (Slower!)
                    EOT,
                'exampleValue' => 'article.content',
            ],
            'content_cleanup' => [
                'name' => '[Optional] Content cleanup: List of items to remove',
                'title' => 'Selector for unnecessary elements to remove inside article contents.',
                'exampleValue' => 'div.ads, div.comments',
            ],
            'title_cleanup' => [
                'name' => '[Optional] Text to remove from expanded article title',
                'title' => <<<EOT
                    When fetching each article page, feed item title comes from page title. 
                    Specify here some text from page title that need to be removed, e.g. " | BlogName".
                    EOT,
                'exampleValue' => ' | BlogName',
            ],
            'discard_thumbnail' => [
                'name' => '[Optional] Discard thumbnail set by site author',
                'title' => 'Some sites set their logo as thumbnail for every article. Use this option to discard it.',
                'type' => 'checkbox',
            ],
            'limit' => self::LIMIT
        ]
    ];

    protected $feedName = '';
    protected $homepageUrl = '';

    public function getURI()
    {
        $url = $this->homepageUrl;
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
        $this->homepageUrl = $this->getInput('home_page');
        $url_selector = $this->getInput('url_selector');
        $url_pattern = $this->getInput('url_pattern');
        $content_selector = $this->getInput('content_selector');
        $content_cleanup = $this->getInput('content_cleanup');
        $title_cleanup = $this->getInput('title_cleanup');
        $discard_thumbnail = $this->getInput('discard_thumbnail');
        $limit = $this->getInput('limit') ?? 10;

        $html = defaultLinkTo(getSimpleHTMLDOM($this->homepageUrl), $this->homepageUrl);
        $this->feedName = $this->titleCleanup($this->getPageTitle($html), $title_cleanup);
        $items = $this->htmlFindEntries($html, $url_selector, $url_pattern, $limit, $content_cleanup);

        if (empty($content_selector)) {
            $this->items = $items;
        } else {
            foreach ($items as $item) {
                $item = $this->expandEntryWithSelector(
                    $item['uri'],
                    $content_selector,
                    $content_cleanup,
                    $title_cleanup,
                    $item['title']
                );
                if ($discard_thumbnail && isset($item['enclosures'])) {
                    unset($item['enclosures']);
                }
                $this->items[] = $item;
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
            $links = array_filter($links, function ($url) use ($url_pattern) {
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
     * @return string Webpage title
     */
    protected function getPageTitle($page)
    {
        if (is_string($page)) {
            $page = getSimpleHTMLDOMCached($page);
        }
        $title = html_entity_decode($page->find('title', 0)->plaintext);
        return $title;
    }

    /**
     * Clean Article title. Remove constant part that appears in every title such as blog name.
     * @param string $title Title to clean, e.g. "Article Name | BlogName"
     * @param string $title_cleanup string to remove from webpage title, e.g. " | BlogName"
     * @return string Cleaned Title
     */
    protected function titleCleanup($title, $title_cleanup)
    {
        if (!empty($title) && !empty($title_cleanup)) {
            return trim(str_replace($title_cleanup, '', $title));
        }
        return $title;
    }

    /**
     * Remove all elements from HTML content matching cleanup selector
     * @param string|object $content HTML content as HTML object or string
     * @return string|object Cleaned content (same type as input)
     */
    protected function cleanArticleContent($content, $cleanup_selector)
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

        if ($string_convert) {
            $content = $content->outertext;
        }
        return $content;
    }

    /**
     * Retrieve first N link+title+truncated-content from webpage URL or DOM satisfying the specified criteria
     * @param string|object $page URL or DOM to retrieve feed items from
     * @param string $url_selector DOM selector for matching links or their parent element
     * @param string $url_pattern Optional filter to keep only links matching the pattern
     * @param int $limit Optional maximum amount of URLs to return
     * @param string $content_cleanup Optional selector for removing elements, e.g. "div.ads, div.comments"
     * @return array of items {'uri': entry_url, 'title': entry_title, ['content': when present in DOM] }
     */
    protected function htmlFindEntries($page, $url_selector, $url_pattern = '', $limit = 0, $content_cleanup = null)
    {
        if (is_string($page)) {
            $page = getSimpleHTMLDOM($page);
        }

        $links = $page->find($url_selector);

        if (empty($links)) {
            returnClientError('No results for URL selector');
        }

        $link_to_item = [];
        foreach ($links as $link) {
            $item = [];
            if ($link->innertext != $link->plaintext) {
                $item['content'] = $link->innertext;
            }
            if ($link->tag != 'a') {
                $link = $link->find('a', 0);
                if (is_null($link)) {
                    continue;
                }
            }
            $item['uri'] = $link->href;
            $item['title'] = $link->plaintext;
            if (isset($item['content'])) {
                $item['content'] = convertLazyLoading($item['content']);
                $item['content'] = defaultLinkTo($item['content'], $item['uri']);
                $item['content'] = $this->cleanArticleContent($item['content'], $content_cleanup);
            }
            $link_to_item[$link->href] = $item;
        }

        if (empty($link_to_item)) {
            returnClientError('The provided URL selector matches some elements, but they do not contain links.');
        }

        $links = $this->filterUrlList(array_keys($link_to_item), $url_pattern, $limit);

        if (empty($links)) {
            returnClientError('No results for URL pattern');
        }

        $items = [];
        foreach ($links as $link) {
            $items[] = $link_to_item[$link];
        }

        return $items;
    }

    /**
     * Retrieve article content from its URL using content selector and return a feed item
     * @param string $entry_url URL to retrieve article from
     * @param string $content_selector HTML selector for extracting content, e.g. "article.content"
     * @param string $content_cleanup Optional selector for removing elements, e.g. "div.ads, div.comments"
     * @param string $title_cleanup Optional string to remove from article title, e.g. " | BlogName"
     * @param string $title_default Optional title to use when could not extract title reliably
     * @return array Entry data: uri, title, content
     */
    protected function expandEntryWithSelector($entry_url, $content_selector, $content_cleanup = null, $title_cleanup = null, $title_default = null)
    {
        if (empty($content_selector)) {
            returnClientError('Please specify a content selector');
        }

        $entry_html = getSimpleHTMLDOMCached($entry_url);
        $item = $this->entryHtmlRetrieveMetadata($entry_html);

        if (empty($item['uri'])) {
            $item['uri'] = $entry_url;
        }

        if (empty($item['title'])) {
            $article_title = $this->getPageTitle($entry_html, $title_cleanup);
            if (!empty($title_default) && (empty($article_title) || $article_title === $this->feedName)) {
                $article_title = $title_default;
            }
            $item['title'] = $article_title;
        }

        $item['title'] = $this->titleCleanup($item['title'], $title_cleanup);

        $article_content = $entry_html->find($content_selector);

        if (!empty($article_content)) {
            $article_content = $article_content[0];
            $article_content = convertLazyLoading($article_content);
            $article_content = defaultLinkTo($article_content, $entry_url);
            $article_content = $this->cleanArticleContent($article_content, $content_cleanup);
            $item['content'] = $article_content;
        } else if (!empty($item['content'])) {
            $item['content'] .= '<br /><p><em>Could not extract full content, selector may need to be updated.</em></p>';
        }

        return $item;
    }

    /**
     * Retrieve metadata from entry HTML: title, author, date published, etc. from metadata intended for social media embeds and SEO
     * @param obj $entry_html DOM object representing the webpage HTML
     * @return array Entry data collected from Metadata
     */
    protected function entryHtmlRetrieveMetadata($entry_html)
    {
        $item = [];

        // == First source of metadata: Meta tags ==
        // Facebook Open Graph (og:KEY) - https://developers.facebook.com/docs/sharing/webmasters
        // Twitter (twitter:KEY) - https://developer.twitter.com/en/docs/twitter-for-websites/cards/guides/getting-started
        // Standard meta tags - https://www.w3schools.com/tags/tag_meta.asp

        // Each Entry field mapping defines a list of possible <meta> tags names that contains the expected value
        static $meta_mappings = [
            // <meta property="article:KEY" content="VALUE" />
            // <meta property="og:KEY" content="VALUE" />
            // <meta property="KEY" content="VALUE" />
            // <meta name="twitter:KEY" content="VALUE" />
            // <meta name="KEY" content="VALUE">
            // <link rel="canonical" href="URL" />
            'uri' => [
                'og:url',
                'twitter:url',
                'canonical'
            ],
            'title' => [
                'og:title',
                'twitter:title'
            ],
            'content' => [
                'og:description',
                'twitter:description',
                'description'
            ],
            'timestamp' => [
                'article:published_time',
                'releaseDate',
                'releasedate',
                'article:modified_time',
                'lastModified',
                'lastmodified'
            ],
            'enclosures' => [
                'og:image:secure_url',
                'og:image:url',
                'og:image',
                'twitter:image',
                'thumbnailImg',
                'thumbnailimg'
            ],
            'author' => [
                'author',
                'article:author',
                'article:author:username',
                'profile:first_name',
                'profile:last_name',
                'article:author:first_name',
                'article:author:last_name',
                'twitter:creator',
            ],
        ];

        $author_first_name = null;
        $author_last_name = null;

        // For each Entry property, look for corresponding HTML tags using a list of candidates
        foreach ($meta_mappings as $property => $field_list) {
            foreach ($field_list as $field) {
                // Look for HTML meta tag
                $element = null;
                if ($field === 'canonical') {
                    $element = $entry_html->find('link[rel=canonical]');
                } else {
                    $element = $entry_html->find("meta[property=$field], meta[name=$field]");
                }
                // Found something? Extract the value and populate Entry field
                if (!empty($element)) {
                    $element = $element[0];
                    $field_value = '';
                    if ($field === 'canonical') {
                        $field_value = $element->href;
                    } else {
                        $field_value = $element->content;
                    }
                    if (!empty($field_value)) {
                        if ($field === 'article:author:first_name' || $field === 'profile:first_name') {
                            $author_first_name = $field_value;
                        } else if ($field === 'article:author:last_name' || $field === 'profile:last_name') {
                            $author_last_name = $field_value;
                        } else {
                            $item[$property] = $field_value;
                            break; // Stop on first match, e.g. og:url has priority over canonical url.
                        }
                    }
                }
            }
        }

        // Populate author from first name and last name if all we have is nothing or Twitter @username
        if ((!isset($item['author']) || $item['author'][0] === '@') && (is_string($author_first_name) || is_string($author_last_name))) {
            $author = '';
            if (is_string($author_first_name)) {
                $author = $author_first_name;
            }
            if (is_string($author_last_name)) {
                $author = $author . ' ' . $author_last_name;
            }
            $item['author'] = trim($author);
        }

        // == Second source of metadata: Embedded JSON ==
        // JSON linked data - https://www.w3.org/TR/2014/REC-json-ld-20140116/
        // JSON linked data is COMPLEX and MAY BE LESS RELIABLE than <meta> tags. Used for fields not found as <meta> tags.
        // The implementation below will load all ld+json we can understand and attempt to extract relevant information.

        // ld+json object types that hold article metadata
        // Each mapping define item fields and a list of possible JSON field for this field
        // Each candiate JSON field is either a string (field name) or a list (path to nested field)
        static $ldjson_article_types = ['webpage', 'article', 'newsarticle', 'blogposting'];
        static $ldjson_article_mappings = [
            'uri' => ['url', 'mainEntityOfPage'],
            'title' => ['headline'],
            'content' => ['description'],
            'timestamp' => ['dateModified', 'datePublished'],
            'enclosures' => ['image'],
            'author' => [['author', 'name'], ['author', '@id'], 'author'],
        ];

        // ld+json object types that hold author metadata
        $ldjson_author_types = ['person', 'organization'];
        $ldjson_author_mappings = []; // ID => Name
        $ldjson_author_id = null;

        // Utility function for checking if JSON array matches one of the desired ld+json object types
        // A JSON object may have a single ld+json @type as a string OR several types at once as a list
        $ldjson_is_of_type = function ($json, $allowed_types) {
            if (isset($json['@type'])) {
                $json_types = $json['@type'];
                if (!is_array($json_types)) {
                    $json_types = [ $json_types ];
                }
                foreach ($json_types as $item_type) {
                    if (in_array(strtolower($item_type), $allowed_types)) {
                        return true;
                    }
                }
            }
            return false;
        };

        // Process ld+json objects embedded in the HTML DOM
        foreach ($entry_html->find('script[type=application/ld+json]') as $html_ldjson_node) {
            $json_raw = json_decode($html_ldjson_node->innertext, true);
            if (is_array($json_raw)) {
                // The JSON we just loaded may contain directly a single ld+json object AND/OR several ones under the '@graph' key
                $json_items = [ $json_raw ];
                if (isset($json_raw['@graph'])) {
                    foreach ($json_raw['@graph'] as $json_raw_sub_item) {
                        $json_items[] = $json_raw_sub_item;
                    }
                }
                // Now that we have a list of distinct JSON items, we can process them individually
                foreach ($json_items as $json) {
                    // JSON item that holds an ld+json Article object (or a variant)
                    if ($ldjson_is_of_type($json, $ldjson_article_types)) {
                        // For each item property, look for corresponding JSON fields and populate the item
                        foreach ($ldjson_article_mappings as $property => $field_list) {
                            // Skip fields already found as <meta> tags, except Twitter @username (because we might find a better name)
                            if (!isset($item[$property]) || ($property === 'author' && $item['author'][0] === '@')) {
                                foreach ($field_list as $field) {
                                    $json_root = $json;
                                    // If necessary, navigate inside the JSON object to access a nested field
                                    if (is_array($field)) {
                                        // At this point, $field = ['author', 'name'] and $json_root = {"author": {"name": "John Doe"}}
                                        $json_navigate_ok = true;
                                        while (count($field) > 1) {
                                            $sub_field = array_shift($field);
                                            if (array_key_exists($sub_field, $json_root)) {
                                                $json_root = $json_root[$sub_field];
                                                if (array_is_list($json_root) && count($json_root) === 1) {
                                                    $json_root = $json_root[0]; // Unwrap list of single item e.g. {"author":[{"name":"John Doe"}]}
                                                }
                                            } else {
                                                // Desired path not found in JSON, stop navigating
                                                $json_navigate_ok = false;
                                                break;
                                            }
                                        }
                                        if (!$json_navigate_ok) {
                                            continue; //Desired path not found in JSON, skip this field
                                        }
                                        $field = $field[0];
                                        // At this point, $field = "name" and $json_root = {"name": "John Doe"}
                                    }
                                    // Now we can check for desired field in JSON and populate $item accordingly
                                    if (isset($json_root[$field])) {
                                        $field_value = $json_root[$field];
                                        if (is_array($field_value) && isset($field_value[0])) {
                                            $field_value = $field_value[0]; // Different versions of the same enclosure? Take the first one
                                        }
                                        if (is_string($field_value) && !empty($field_value)) {
                                            if ($property === 'author' && $field === '@id') {
                                                $ldjson_author_id = $field_value; // Author is referred to by its ID: We'll see later if we can resolve it
                                            } else {
                                                $item[$property] = $field_value;
                                                break; // Stop on first match, e.g. {"author":{"name":"John Doe"}} has priority over {"author":"John Doe"}
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    // JSON item that holds an ld+json Author object (or a variant)
                    } else if ($ldjson_is_of_type($json, $ldjson_author_types)) {
                        if (isset($json['@id']) && isset($json['name'])) {
                            $ldjson_author_mappings[$json['@id']] = $json['name'];
                        }
                    }
                }
            }
        }

        // Attempt to resolve ld+json author if all we have is nothing or Twitter @username
        if ((!isset($item['author']) || $item['author'][0] === '@') && !is_null($ldjson_author_id) && isset($ldjson_author_mappings[$ldjson_author_id])) {
            $item['author'] = $ldjson_author_mappings[$ldjson_author_id];
        }

        // Adjust item field types
        if (isset($item['enclosures'])) {
            $item['enclosures'] = [ $item['enclosures'] ];
        }
        if (isset($item['timestamp'])) {
            $item['timestamp'] = strtotime($item['timestamp']);
        }

        return $item;
    }
}
