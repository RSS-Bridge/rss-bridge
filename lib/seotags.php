<?php

/**
 * Process HTML DOM to retrieve standard metadata intended for social media embeds and SEO.
 * @param string|object $html Webpage HTML. Supports HTML objects or string objects.
 * @return array Entry generated from Metadata: 'title', 'author', 'timestamp', etc.
 */
function html_find_seo_metadata($html)
{
    if (is_string($html)) {
        $html = getSimpleHTMLDOM($html);
    }

    $item = [];

    // == First source of metadata: Meta tags ==
    // Facebook Open Graph (og:KEY) - https://developers.facebook.com/docs/sharing/webmasters
    // Twitter (twitter:KEY) - https://developer.twitter.com/en/docs/twitter-for-websites/cards/guides/getting-started
    // Standard meta tags - https://www.w3schools.com/tags/tag_meta.asp
    // Standard time tag - https://developer.mozilla.org/en-US/docs/Web/HTML/Element/time

    // Each Entry field mapping defines a list of possible <meta> tags names that contains the expected value
    // There are various source candidates per type of data, listed from most reliable to least reliable
    static $meta_mappings = [
        // <meta property="article:KEY" content="VALUE" />
        // <meta property="og:KEY" content="VALUE" />
        // <meta property="KEY" content="VALUE" />
        // <meta name="twitter:KEY" content="VALUE" />
        // <meta name="KEY" content="VALUE">
        // <link rel="canonical" href="URL" />
        // <time datetime="VALUE">text</time>
        'uri' => [
            'og:url',
            'twitter:url',
            'canonical',
        ],
        'title' => [
            'og:title',
            'twitter:title',
        ],
        'content' => [
            'og:description',
            'twitter:description',
            'description',
        ],
        'timestamp' => [
            'article:published_time',
            'og:article:published_time',
            'releaseDate',
            'releasedate',
            'article:modified_time',
            'og:article:modified_time',
            'lastModified',
            'lastmodified',
            'time',
        ],
        'enclosures' => [
            'og:image:secure_url',
            'og:image:url',
            'og:image',
            'twitter:image',
            'thumbnailImg',
            'thumbnailimg',
        ],
        'author' => [
            'article:author',
            'og:article:author',
            'author',
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
                $element = $html->find('link[rel=canonical]');
            } else if ($field === 'time') {
                $element = $html->find('time[datetime]');
            } else {
                $element = $html->find("meta[property=$field], meta[name=$field]");
            }
            // Found something? Extract the value and populate Entry field
            if (!empty($element)) {
                $element = $element[0];
                $field_value = '';
                if ($field === 'canonical') {
                    $field_value = $element->href;
                } else if ($field === 'time') {
                    $field_value = $element->datetime;
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
    foreach ($html->find('script[type=application/ld+json]') as $html_ldjson_node) {
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
