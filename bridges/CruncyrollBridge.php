<?php

/**
 * Crunchyroll News Bridge for RSS-Bridge
 *
 * This bridge fetches the latest news from the Crunchyroll News API and provides it as an RSS feed.
 * 
 * Parameters:
 * - category: The category of news to fetch (e.g., Announcements, News, etc.).
 * - page_size: The number of results to return per page (default: 16).
 * - page: The page number for paginated results (default: 1).
 *
 * @author YourName
 */

class CrunchyrollBridge extends BridgeAbstract {
    // Metadata about the bridge
    const NAME = 'Crunchyroll News Bridge';
    const URI = 'https://www.crunchyroll.com/news';
    const DESCRIPTION = 'Returns the latest news from Crunchyroll';
    const MAINTAINER = 'YourName';

    // Input parameters for the bridge
    const PARAMETERS = [
        [
            'category' => [
                'name' => 'Category',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'Announcements,News,News',
            ],
            'page_size' => [
                'name' => 'Page Size',
                'type' => 'number',
                'required' => false,
                'defaultValue' => 16,
            ],
            'page' => [
                'name' => 'Page',
                'type' => 'number',
                'required' => false,
                'defaultValue' => 1,
            ],
        ]
    ];

    /**
     * Collects data from the Crunchyroll API and formats it as an RSS feed.
     */
    public function collectData() {
        // Define API base URL
        $apiBaseUrl = 'https://cr-news-api-service.prd.crunchyrollsvc.com/v1/en-US/stories/search';

        // Retrieve input parameters
        $category = $this->getInput('category');
        $pageSize = $this->getInput('page_size');
        $page = $this->getInput('page');

        // Construct the API URL
        $apiUrl = sprintf(
            '%s?category=%s&page_size=%d&page=%d',
            $apiBaseUrl,
            urlencode($category),
            $pageSize,
            $page
        );

        // Define HTTP headers for the API request
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (compatible; RSSBridge/2025)',
                    'Accept: application/json',
                    'Accept-Language: en-US,en;q=0.5',
                    'Origin: https://www.crunchyroll.com',
                    'Referer: https://www.crunchyroll.com/',
                ],
            ],
        ];

        // Create a context for the HTTP request
        $context = stream_context_create($options);

        // Fetch data from the API
        $response = file_get_contents($apiUrl, false, $context);

        if ($response === false) {
            throw new Exception('Failed to fetch data from the Crunchyroll API.');
        }

        // Parse the JSON response
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode JSON response: ' . json_last_error_msg());
        }

        // Process each story in the response
        foreach ($data['stories'] as $story) {
            $item = [];

            // Set the item properties
            $item['uri'] = self::URI . '/' . $story['slug'];
            $item['title'] = $story['content']['headline'];
            $item['timestamp'] = strtotime($story['content']['article_date']);
            $item['author'] = 'Unknown'; // Author data can be added if available
            $item['content'] = sprintf(
                '<img src="%s" alt="%s"><br>%s',
                $story['content']['thumbnail']['filename'],
                htmlspecialchars($story['content']['thumbnail']['alt']),
                htmlspecialchars($story['content']['lead'])
            );
            $item['categories'] = $story['tag_list'] ?? [];
            $item['uid'] = $story['uuid'];

            // Add the item to the feed
            $this->items[] = $item;
        }
    }
}
