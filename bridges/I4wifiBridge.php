<?php

/**
 *
 * The website i4wifi.cz is a wholesale distributor specializing in wireless, networking, and photovoltaic equipment, offering products from brands like MikroTik, Ubiquiti, and Hikvision. It provides a wide range of network solutions, technical support, and training services for businesses and professional installers in the Czech Republic and beyond.
 */

class I4wifiBridge extends BridgeAbstract
{
    const NAME = 'i4wifi Bridge';
    const URI = 'https://www.i4wifi.cz';
    const DESCRIPTION = 'Product news not only from the wireless, network and security technology sector from i4wifi.cz - Czech Republic';
    const MAINTAINER = 'pprenghyorg';

    // Only Articles are supported
    const PARAMETERS = [
        'Product news' => [
        ],
    ];

    /**
     * Fetches and processes data based on the selected context.
     *
     * This function retrieves the HTML content for the specified context's URI,
     * resolves relative links within the content, and then delegates the data
     * extraction to the appropriate method (currently only `collectNews`).
     */

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getURI(), 86400);

        defaultLinkTo($html, static::URI);

        // Router
        switch ($this->queriedContext) {
            case 'Product news':
                $this->collectNews($html);
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
            case 'Product news':
                $uri .= '/';
                break;
        }

        return $uri;
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
            case 'Product news':
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
        $df = $this->parseDateTimeFromString($date);

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

    #region Articles

    /**
     * Collects uri, timestamp, title, content and images in the news articles from the HTML and transforms to rss.
     *
     * @param object $html The HTML object.
     * @return void
     */
    private function collectNews($html)
    {
        $articles = $html->find('.timeline-item.timeline-item-right')
            or throwServerException('No articles found! Layout might have changed!');

        foreach ($articles as $article) {
            $item = [];

            // get uri of product
            $item['uri'] = $this->extractNewsUri($article);
            // Add content
            $item['content'] = $this->extractNewsDescription($article);
            // Add images
            $item['title'] = $this->extractNewsTitle($article);
            // Add images
            $item['enclosures'] = $this->extractImages($article);
            // Add timestamp
            $item['timestamp'] = $this->extractNewsDate($article);

            // collect sources into rss article
            $this->items[] = $item;
        }
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
        $element = $article->find('a', 0)
            or throwServerException('Anchor not found!');

        return $element->href;
    }

    /**
     * Extracts the date of the news article.
     *
     * @param object $article The article object.
     * @return string The date of the news article.
     */
    private function extractNewsDate($article)
    {
        // Check if date is set
        $element = $article->find('.timeline-item-info', 0)
            or throwServerException('Date not found!');

        // Format date
        return $this->fixDate($element->plaintext);
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
        $element = $article->find('p', 0)
            or throwServerException('Description not found!');

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
        $element = $article->find('img', 0)
            or throwServerException('Title not found!');

        return $element->alt;
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
            'd. m. Y H:i:s', // 10. 04. 2025 10:57:47
            'j. n. Y H:i:s', // 10. 4. 2025 10:57:47
            'd.m.Y H:i',    // 10.04.2025 10:57
            'j.n.Y H:i',    // 10.4.2025 10:57
            'd. m. Y H:i',   // 10. 04. 2025 10:57
            'j. n. Y H:i',   // 10. 4. 2025 10:57
            'd.m.Y',        // 10.04.2025
            'j.n.Y',        // 10.4.2025
            'd. m. Y',       // 10. 04. 2025
            'j. n. Y',       // 10. 4. 2025

            // ISO 8601 and international formats (year-month-day)
            'Y-m-d H:i:s',  // 2025-04-10 10:57:47
            'Y-m-d H:i',    // 2025-04-10 10:57
            'Y-m-d',        // 2025-04-10
            'YmdHis',       // 20250410105747
            'Ymd',          // 20250410

            // American formats (month/day/year) - beware of ambiguity!
            'm/d/Y H:i:s',  // 04/10/2025 10:57:47
            'n/j/Y H:i:s',  // 4/10/2025 10:57:47
            'm/d/Y H:i',    // 04/10/2025 10:57
            'n/j/Y H:i',    // 4/10/2025 10:57
            'm/d/Y',        // 04/10/2025
            'n/j/Y',        // 4/10/2025

            // Standard formats (including time zone)
            DateTime::ATOM,             // example. 2025-04-10T10:57:47+02:00
            DateTime::RFC3339,          // example. 2025-04-10T10:57:47+02:00
            DateTime::RFC3339_EXTENDED, // example. 2025-04-10T10:57:47.123+02:00
            DateTime::RFC2822,          // example. Thu, 10 Apr 2025 10:57:47 +0200
            DateTime::ISO8601,          // example. 2025-04-10T105747+0200
            'Y-m-d\TH:i:sP',            // ISO 8601 s 'T' oddělovačem
            'Y-m-d\TH:i:s.uP',          // ISO 8601 s mikrosekundami

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

    #endregion
}
