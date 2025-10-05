<?php

// TODO: dokumentace
// TODO: datum u clanku
// TODO: odstranit nadbytecny parametr - topic

class StavebninyIZOMATBridge extends BridgeAbstract
{
    const NAME = 'Stavebniny IZOMAT Bridge';
    const URI = 'https://www.izomat.cz';
    const DESCRIPTION = 'News, events and promotions on IZOMAT building materials shop - www.izomat.cz - Czech Republic';
    const MAINTAINER = 'pprenghyorg';

    // Only Weekly offer and Promotional letter are supported
    const PARAMETERS = [
        'News' => [],
        'Blog' => []
//        'Promo' => []
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        defaultLinkTo($html, static::URI);

        // Router
        switch ($this->queriedContext) {
            case 'News':
                $this->collectNews($html);
                break;
            case 'Blog':
                $this->collectBlog($html);
                break;
            case 'Promo':
                $this->collectPromo($html);
                break;
        }
    }

    /**
     * Returns the icon for the bridge.
     *
     * @return string The icon URL.
     */
    public function getURI()
    {
        $uri = static::URI;

        // URI Router
        switch ($this->queriedContext) {
            case 'News':
                $uri .= '/aktuality/';
                break;
            case 'Blog':
                $uri .= '/blog/';
                break;
            case 'Promo':
                $uri .= '/';
                break;
        }

        return $uri;
    }

    /**
     * Returns the keyword URL map for the bridge.
     *
     * @return string The Name.
     */
    public function getKeywordUrlMap()
    {
        // Get the keyword URL map from the class constant
        $keywordUrlMap = static::KEYWORDURLMAP;

        // returns the keyword URL map
        return $keywordUrlMap;
    }

    /**
     * Returns the name for the bridge.
     *
     * @return string The Name.
     */
    public function getName()
    {
        $name = static::NAME;

        $name .= ($this->queriedContext) ? ' - ' . $this->queriedContext : '';

        switch ($this->queriedContext) {
            case 'News':
                break;
            case 'Blog':
                break;
            case 'Promo':
                break;
        }

        return $name;
    }

    /**
     * Parse most used date formats
     *
     * Basically strtotime doesn't convert dates correctly due to formats
     * being hard to interpret. So we use the DateTime object, manually
     * fixing dates and times (set to 00:00:00.000).
     *
     * We don't know the timezone, so just assume +00:00 (or whatever
     * DateTime chooses)
     */
    private function fixDate($date)
    {
        echo $date;
        $df = $this->parseDateTimeFromString($date);
        echo $df;
        exit;

        return date_format($df, 'U');
    }

    /**
     * Extracts the images from the article.
     *
     * @param object $article The article object.
     * @return array An array of image URLs.
     */
    private function extractImages($article)
    {
        // Notice: We can have zero or more images (though it should mostly be 1)
        $elements = $article->find('img');

        $images = [];

        foreach ($elements as $img) {
            $images[] = $img->src;
        }

        return $images;
    }

    // region Weekly offer

    /**
     * Collects uri, timestamp, title, content and images in the product offers from the HTML and transforms to rss.
     *
     * @param object $html The HTML object.
     * @return void
     */
    private function collectNews($html)
    {
        // Check if page contains articles and split by class
        $articles = $html->find('.col-12.col-md-6') or
            returnServerException('No articles found! Layout might have changed!');

        // Articles loop
        foreach ($articles as $article) {
            $item = [];

            if (!($this->isValidUrl($this->extractNewsUri($article)))) {
                continue;
            }

            // Add URI
            $item['uri'] = $this->extractNewsUri($article);

            // Add title
            $item['title'] = $this->extractNewsTitle($article);

            // Add content
            $item['content'] = $this->extractNewsDescription($article);

            // Add images
            $imgset = [];
            $imgset[] = $this->extractNewsIMG($article);
            $item['enclosures'] = $imgset;
            $this->items[] = $item;
        }
    }

    /**
     * Collects uri, timestamp, title, content and images in the promotional letter from the HTML and transforms to rss.
     *
     * @param object $html The HTML object.
     * @return void
     */
    private function collectBlog($html)
    {
        // Check if page contains articles and split by class
        $articles = $html->find('div.col-12.col-md-6') or
            returnServerException('No articles found! Layout might have changed!');

        // Articles loop
        foreach ($articles as $article) {
            $item = [];

            // Check if page contains articles and split by class
            if (!($this->isValidUrl($this->extractNewsUri($article)))) {
                continue;
            }

            // Add URI
            $item['uri'] = $this->extractNewsUri($article);

            // Add title
            $item['title'] = $this->extractBlogTitle($article);

            // Add content
            $item['content'] = $this->extractBlogDescription($article);

            // Add images
            $imgset = [];
            $imgset[] = $this->extractNewsIMG($article);
            $item['enclosures'] = $imgset;

            // Add to rss query
            $this->items[] = $item;
        }
    }

    /**
     * Collects uri, timestamp, title, content and images in the promotional letter from the HTML and transforms to rss.
     *
     * @param object $html The HTML object.
     * @return void
     */
    private function collectPromo($html)
    {
        // Check if page contains articles and split by class
        $articles = $html->find('div.product-wrap') or
            returnServerException('No articles found! Layout might have changed!');

        // Articles loop
        foreach ($articles as $article) {
            $item = [];

            // Add URI
            $item['uri'] = $this->extractNewsUri($article);
            echo $item['uri'] . '<BR>';
/*
            // Add title
            $item['title'] = $this->extractEventTitle($article);
            // Add content
            $item['content'] = $this->extractEventDescription($article);
            // Parse time
            $newsDate = $this->extractDate($article);
            // Remove prefix
            $newsDate = str_replace("zveřejněno: ", "", $newsDate);
            // Fix date
            $item['timestamp'] = $this->fixDate($newsDate);
            // Add images
            $item['enclosures'] = $this->extractImages($article);

            // Add to rss query
            $this->items[] = $item;
*/
        }
    }

    /**
     * Extracts the IMG of the news article.
     *
     * @param object $article The article object.
     * @return string The URI of the news article.
     */
    private function extractNewsIMG($article)
    {
        // Return URI of the article
        $element = $article->find('.img-fluid', 0);

        // Element empty check
        if ($element == null) {
            return '';
        }

        return $element->getAttribute('data-src');
    }

    /**
     * Extracts the IMG of the news article.
     *
     * @param object $article The article object.
     * @return string The URI of the news article.
     */
    private function extractBlogIMG($article)
    {
        // Return URI of the article
        $element = $article->find('.img-fluid.loaded', 0);

        // Element empty check
        if ($element == null) {
            return '';
        }

        return $element->getAttribute('data-src');
    }

    /**
     * Extracts the URI of the news article.
     *
     * @param object $article The article object.
     * @return string The URI of the news article.
     */
    private function extractNewsUri($article)
    {
        // Return URI of the article
        $element = $article->find('a', 0);

        // Element empty check
        if ($element == null) {
            return '';
        }

        return $element->href;
    }

    /**
     * Extracts the URI of the news article.
     *
     * @param object $article The article object.
     * @return string The URI of the news article.
     */
    private function extractLetterUri($article)
    {
        // Return URI of the article
        $element = $article->find('a.ws-btn', 0);

        // Element empty check
        if ($element == null) {
            return '';
        }

        return $element->href;
    }

    /**
     * Extracts the date of the news article.
     *
     * @param object $article The article object.
     * @return string The date of the news article.
     */
    private function extractDate($article)
    {
        // Check if date is set
        $element = $article->find('div.blog-article__date', 0) or
            returnServerException('Date not found!');

        return $element->plaintext;
    }

    /**
     * Extracts the description of the news article.
     *
     * @param object $article The article object.
     * @return string The description of the news article.
     */
    private function extractNewsDescription1($article)
    {
        // Extract description
        $element = $article->find('div.ws-product-price-validity', 0)->find('div', 0) or
            returnServerException('Description not found!');

        return $element->innertext;
    }

    /**
     * Extracts the description of the news article.
     *
     * @param object $article The article object.
     * @return string The description of the news article.
     */
    private function extractBlogDescription($article)
    {
        // Extract description
        $element = $article->find('p.blog-article__text', 0) or
            returnServerException('Description not found!');

        return $element->innertext;
    }

    /**
     * Extracts the description of the news article.
     *
     * @param object $article The article object.
     * @return string The description of the news article.
     */
    private function extractNewsDescription2($article)
    {
        // Extract description
        $element = $article->find('div.ws-product-price-validity', 0)->find('div', 1) or
            returnServerException('Description not found!');

        return $element->innertext;
    }

    /**
     * Extracts the description of the news article.
     *
     * @param object $article The article object.
     * @return string The description of the news article.
     */
    private function extractNewsDescription3($article)
    {
        // Extract description
        $element = $article->find('div.ws-product-badge-text', 0);

        // Check if element is not null
        // If it is null, return empty string
        // If it is not null, return the inner text
        // This is to avoid errors when the element is not found
        // and to ensure that the function always returns a string
        if ($element != null) {
            return $element->innertext;
        } else {
            return '';
        }
    }

    /**
     * Extracts the description of the news article.
     *
     * @param object $article The article object.
     * @return string The description of the news article.
     */
    private function extractNewsDescription4($article)
    {
        // Extract description
        $element = $article->find('div.ws-product-price-type__value', 0);

        return $element->innertext;
    }

    /**
     * Extracts the description of the news article.
     *
     * @param object $article The article object.
     * @return string The description of the news article.
     */
    private function extractNewsDescription5($article)
    {
        // Extract description
        $element = $article->find('div.ws-product-price-type__label', 0);

        return $element->innertext;
    }

    /**
     * Extracts the description of the news article.
     *
     * @param object $article The article object.
     * @return string The description of the news article.
     */
    private function extractNewsDescription6($article)
    {
        // Extract description
        $element = $article->find('div.ws-product-price', 0)->find('div.ws-product-price-type', 1);

        // Element empty check
        if ($element == null) {
            return '';
        }

        // Not null, so we can safely access the element
        $element = $element->find('div.ws-product-price-type__value', 0);

        return $element->innertext;
    }

    /**
     * Extracts the description of the news article.
     *
     * @param object $article The article object.
     * @return string The description of the news article.
     */
    private function extractNewsDescription($article)
    {
        // Extract description
        $element = $article->find('.blog-article__text.content-text.card-text.articles-desc', 0);

        return $element->innertext;
    }

    /**
     * Extracts the title of the news article.
     *
     * @param object $article The article object.
     * @return string The title of the news article.
     */
    private function extractBlogTitle($article)
    {
        // Extract title
        $element = $article->find('a.blog-article__link', 0) or
            returnServerException('Title not found!');

        return $element->innertext;
    }

    /**
     * Extracts the title of the news article.
     *
     * @param object $article The article object.
     * @return string The title of the news article.
     */
    private function extractNewsTitle($article)
    {
        // Extract title
        $element = $article->find('a.blog-article__link', 0) or
            returnServerException('Title not found!');

        return $element->innertext;
    }

    /**
     * Extracts the title of the news article.
     *
     * @param object $article The article object.
     * @return string The title of the news article.
     */
    private function extractEventTitle($article)
    {
        // Extract title
        $element = $article->find('div.com-news-common-prerex__right-box', 0)->find('h3', 0)
            or returnServerException('Title not found!');

        return $element->plaintext;
    }

    /**
     * Extracts the description of the letter article.
     *
     * @param object $article The article object.
     * @return string The description of the news article.
     */
    private function extractLetterDescription($article)
    {
        // Extract description
        $element = $article->find('a', 0);

        return $element;
    }

    /**
     * It attempts to recognize the date/time format in a string and create a DateTime object.
     *
     * It goes through the list of defined formats and tries to apply them to the input string.
     * Returns the first successfully parsed DateTime object that matches the entire string.
     *
     * @param string $dateString A string potentially containing a date and/or time.
     * @return DateTime|null A DateTime object if successfully recognized and parsed, otherwise null.
     */
    private function parseDateTimeFromString(string $dateString): ?DateTime
    {
        // List of common formats - YOU CAN AND SHOULD EXPAND IT according to expected inputs!
        // Order may matter if the formats are ambiguous.
        // It is recommended to give more specific formats (with time, full year) before more general ones.
        $possibleFormats = [
            // Czech formats (day.month.year)
            'd.m.Y H:i:s',  // 10.04.2025 10:57:47
            'j.n.Y H:i:s',  // 10.4.2025 10:57:47
            'd. m. Y H:i:s',  // 10. 04. 2025 10:57:47
            'j. n. Y H:i:s',  // 10. 4. 2025 10:57:47
            'd.m.Y H:i',  // 10.04.2025 10:57
            'j.n.Y H:i',  // 10.4.2025 10:57
            'd. m. Y H:i',  // 10. 04. 2025 10:57
            'j. n. Y H:i',  // 10. 4. 2025 10:57
            'd.m.Y',  // 10.04.2025
            'j.n.Y',  // 10.4.2025
            'd. m. Y',  // 10. 04. 2025
            'j. n. Y',  // 10. 4. 2025
            // ISO 8601 and international formats (year-month-day)
            'Y-m-d H:i:s',  // 2025-04-10 10:57:47
            'Y-m-d H:i',  // 2025-04-10 10:57
            'Y-m-d',  // 2025-04-10
            'YmdHis',  // 20250410105747
            'Ymd',  // 20250410
            // American formats (month/day/year) - beware of ambiguity!
            'm/d/Y H:i:s',  // 04/10/2025 10:57:47
            'n/j/Y H:i:s',  // 4/10/2025 10:57:47
            'm/d/Y H:i',  // 04/10/2025 10:57
            'n/j/Y H:i',  // 4/10/2025 10:57
            'm/d/Y',  // 04/10/2025
            'n/j/Y',  // 4/10/2025
            // Standard formats (including time zone)
            DateTime::ATOM,  // example. 2025-04-10T10:57:47+02:00
            DateTime::RFC3339,  // example. 2025-04-10T10:57:47+02:00
            DateTime::RFC3339_EXTENDED,  // example. 2025-04-10T10:57:47.123+02:00
            DateTime::RFC2822,  // example. Thu, 10 Apr 2025 10:57:47 +0200
            DateTime::ISO8601,  // example. 2025-04-10T105747+0200
            'Y-m-d\TH:i:sP',  // ISO 8601 s 'T' oddělovačem
            'Y-m-d\TH:i:s.uP',  // ISO 8601 s mikrosekundami
            // You can add more formats as needed...
            // e.g. 'd-M-Y' (10-Apr-2025) - requires English locale
            // e.g. 'j. F Y' (10. abren 2025) - requires Czech locale
        ];

        // Set locale for parsing month/day names (if using F, M, l, D)
        // E.g. setlocale(LC_TIME, 'cs_CZ.UTF-8'); or 'en_US.UTF-8');

        foreach ($possibleFormats as $format) {
            // We will try to create a DateTime object from the given format
            $dateTime = DateTime::createFromFormat($format, $dateString);

            // We check that the parsing was successful AND ALSO
            // that there were no errors or warnings during the parsing.
            // This is important to ensure that the format matches the ENTIRE string.
            if ($dateTime !== false) {
                $errors = DateTime::getLastErrors();
                if (!($errors)) {
                    // Success! We found a valid format for the entire string.
                    return $dateTime;
                }
            }
        }

        // If no format matches or parsing failed
        return null;
    }

    /**
     * Finds values from an associative array whose keys are substrings of a given text.
     *
     * The function iterates through the `$map` associative array. For each key,
     * it checks if that key exists as a substring within the input `$text`.
     * If found, the corresponding value from the map is added to the result array.
     * The search is case-sensitive and treats special characters literally.
     *
     * @param string $text The input text string to search within.
     * @param array $map An associative array (key => value). Keys from this array will be searched for in `$text`.
     * @return array An array of values whose corresponding keys were found as substrings in `$text`. Returns an empty array if no keys are found.
     */
    private function findValuesByKeySubstring(string $text, array $map): array
    {
        $foundValues = [];  // Initialize array for found values

        // Iterate through each key => value pair in the map
        foreach ($map as $key => $value) {
            // Use strpos(), which finds the position of the first occurrence of a substring.
            // Returns the position (including 0) or `false` if the substring is not found.
            // We use `!== false` to correctly handle the case where the key starts at position 0.
            // Cast key to string for robustness (though array keys are usually strings or ints).
            // `strpos` treats special characters in the key and text literally.

            //          echo "Key: $key, Text: $text<BR>\n";
            if (strpos($text, $key) !== false) {
                // If the key was found in the text, add its corresponding value to the result array
                $foundValues[] = $value;
            }
        }

        // Return the array of found values
        return $foundValues;
    }

    /**
     * Removes Czech diacritics from a given string.
     *
     * This function replaces Czech characters with their ASCII equivalents.
     * For example, 'á' becomes 'a', 'č' becomes 'c', etc.
     *
     * @param string $text The input string with Czech diacritics.
     * @return string The string with Czech diacritics removed.
     */
    private function removeCzechDiacritics(string $text): string
    {
        $czech = [
            'á', 'č', 'ď', 'é', 'ě', 'í', 'ň', 'ó', 'ř', 'š', 'ť', 'ú', 'ů', 'ý', 'ž',
            'Á', 'Č', 'Ď', 'É', 'Ě', 'Í', 'Ň', 'Ó', 'Ř', 'Š', 'Ť', 'Ú', 'Ů', 'Ý', 'Ž'
        ];
        $ascii = [
            'a', 'c', 'd', 'e', 'e', 'i', 'n', 'o', 'r', 's', 't', 'u', 'u', 'y', 'z',
            'A', 'C', 'D', 'E', 'E', 'I', 'N', 'O', 'R', 'S', 'T', 'U', 'U', 'Y', 'Z'
        ];

        return str_replace($czech, $ascii, $text);
    }

    // endregion

    /**
     * formatTitleFromURI
    */
    private function formatTitleFromURI(string $uri): string
    {
        // get last part of the URI
        $title = basename($uri);

        // Pattern: /[^\p{L}\p{N}]+/u
        // [^...] - Match any character NOT in the set
        // \p{L}  - Any Unicode letter (including 'é', 'ü', 'ñ', etc.)
        // \p{N}  - Any Unicode number (0-9 and other numeric characters)
        // +      - Match one or more occurrences of the preceding pattern consecutively
        // /u     - Unicode modifier, essential for \p{} constructs
        $pattern = '/[^\p{L}\p{N}]+/u';
        $replacement = ' '; // Replace with a single space

        // lets replace
        $title = preg_replace($pattern, $replacement, $title);

        // first letter to uppercase
        $title = ucfirst($title);

        return trim((string)$title);
    }

    /**
     * Checks if the given string is a valid URL.
     *
     * This function uses the PHP filter FILTER_VALIDATE_URL.
     * It requires the presence of a scheme (e.g., "http://", "https://") for successful validation.
     *
     * @param string $url The string to be checked.
     * @return bool Returns true if the string is a valid URL, false otherwise.
     */
    private function isValidUrl(string $url): bool
    {
        // Use filter_var with the FILTER_VALIDATE_URL filter.
        // This function returns the filtered URL on success (which is not false),
        // or false on validation failure.
        // Comparing with !== false ensures returning an actual boolean true/false value.
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}