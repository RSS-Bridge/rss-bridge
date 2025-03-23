<?php

class BazarakiBridge extends BridgeAbstract
{
    const NAME = 'Bazaraki Bridge';
    const URI = 'https://bazaraki.com';
    const DESCRIPTION = 'Fetch adverts from Bazaraki, a Cyprus-based classifieds website.';
    const MAINTAINER = 'danwain';
    const PARAMETERS = [
        [
            'url' => [
                'name'         => 'URL',
                'type'         => 'text',
                'required'     => true,
                'title'        => 'Enter the URL of the Bazaraki page to fetch adverts from.',
                'exampleValue' => 'https://www.bazaraki.com/real-estate-for-sale/houses/?lat=0&lng=0&radius=100000',
            ],
            'limit' => [
                'name'         => 'Limit',
                'type'         => 'number',
                'required'     => false,
                'title'        => 'Enter the number of adverts to fetch. (max 50)',
                'exampleValue' => '10',
                'defaultValue' => 10,
            ]
        ]
    ];

    public function collectData()
    {
        $url = $this->getInput('url');
        if (! str_starts_with($url, 'https://www.bazaraki.com/')) {
            throw new \Exception('Nope');
        }

        $html = getSimpleHTMLDOM($url);

        $i = 0;
        foreach ($html->find('div.advert') as $element) {
            $i++;
            if ($i > $this->getInput('limit') || $i > 50) {
                break;
            }

            $item = [];

            $item['uri'] = 'https://www.bazaraki.com' . $element->find('a.advert__content-title', 0)->href;

            # Get the content
            $advert = getSimpleHTMLDOM($item['uri']);

            $price = trim($advert->find('div.announcement-price__cost', 0)->plaintext);
            $name  = trim($element->find('a.advert__content-title', 0)->plaintext);

            $item['title'] = $name . ' - ' . $price;

            $time = trim($advert->find('span.date-meta', 0)->plaintext);
            $time = str_replace('Posted: ', '', $time);


            $item['content'] = $this->processAdvertContent($advert);
            $item['timestamp'] = $this->convertRelativeTime($time);
            $item['author'] = trim($advert->find('div.author-name', 0)->plaintext);
            $item['uid'] = $advert->find('span.number-announcement', 0)->plaintext;

            $this->items[] = $item;
        }
    }

    /**
     * Process the advert content to clean up HTML
     *
     * @param simple_html_dom $advert The SimpleHTMLDOM object for the advert page
     * @return string Processed HTML content
     */
    private function processAdvertContent($advert)
    {
        // Get the content sections
        $header = $advert->find('div.announcement-content-header', 0);
        $characteristics = $advert->find('div.announcement-characteristics', 0);
        $description = $advert->find('div.js-description', 0);
        $images = $advert->find('div.announcement__images', 0);

        // Remove all favorites divs
        foreach ($advert->find('div.announcement-meta__favorites') as $favorites) {
            $favorites->outertext = '';
        }

        // Replace all <a> tags with their text content
        foreach ($advert->find('a') as $a) {
            $a->outertext = $a->innertext;
        }

        // Format the content with section headers and dividers
        $formattedContent = '';

        // Add header section
        $formattedContent .= $header->innertext;
        $formattedContent .= '<hr/>';

        // Add characteristics section with header
        $formattedContent .= '<h3>Details</h3>';
        $formattedContent .= $characteristics->innertext;
        $formattedContent .= '<hr/>';

        // Add description section with header
        $formattedContent .= '<h3>Description</h3>';
        $formattedContent .= $description->innertext;
        $formattedContent .= '<hr/>';

        // Add images section with header
        $formattedContent .= '<h3>Images</h3>';
        $formattedContent .= $images->innertext;

        return $formattedContent;
    }

    /**
     * Convert relative time strings like "Yesterday 12:32" to proper timestamps
     *
     * @param string $timeString The relative time string from the website
     * @return string Timestamp in a format compatible with strtotime()
     */
    private function convertRelativeTime($timeString)
    {
        if (strpos($timeString, 'Yesterday') !== false) {
            // Replace "Yesterday" with actual date
            $time = str_replace('Yesterday', date('Y-m-d', strtotime('-1 day')), $timeString);
            return date('Y-m-d H:i:s', strtotime($time));
        } elseif (strpos($timeString, 'Today') !== false) {
            // Replace "Today" with actual date
            $time = str_replace('Today', date('Y-m-d'), $timeString);
            return date('Y-m-d H:i:s', strtotime($time));
        } else {
            // For other formats, return as is and let strtotime handle it
            return $timeString;
        }
    }
}
