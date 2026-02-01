<?php

/**
 * CrunchyrollBridge fetches news articles from the Crunchyroll API.
 */
class CrunchyrollBridge extends BridgeAbstract
{
    const NAME = 'Crunchyroll News Bridge';
    const URI = 'https://www.crunchyroll.com/news';
    const DESCRIPTION = 'Returns latest news from Crunchyroll';
    const MAINTAINER = 'peppy6582';
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
        ],
    ];

    /**
     * Collects data from the Crunchyroll API and populates items.
     *
     * @throws Exception If the API call or JSON decoding fails.
     */
    public function collectData()
    {
        // Define API base URL
        $apiBaseUrl = 'https://cr-news-api-service.prd.crunchyrollsvc.com/v1/en-US/stories/search';

        // Retrieve input parameters
        $category = $this->getInput('category');
        $pageSize = $this->getInput('page_size');
        $page = $this->getInput('page');

        // Construct the API URL with query parameters
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

        // Use getContents for better error handling and compliance
        $response = getContents($apiUrl, [], $options);

        if ($response === false) {
            throw new Exception('Failed to fetch data from the Crunchyroll API.');
        }

        // Parse the JSON response
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode JSON response: ' . json_last_error_msg());
        }

        // Map UUIDs to author names from the `rels` array
        $authorMap = [];
        foreach ($data['rels'] as $rel) {
            if (isset($rel['uuid'], $rel['content']['name'])) {
                $authorMap[$rel['uuid']] = $rel['content']['name'];
            }
        }

        // Process each story in the response
        foreach ($data['stories'] as $story) {
            $item = [];

            // Find the author name(s) for the story
            $authorNames = [];
            if (!empty($story['content']['authors'])) {
                foreach ($story['content']['authors'] as $authorUuid) {
                    if (isset($authorMap[$authorUuid])) {
                        $authorNames[] = $authorMap[$authorUuid];
                    }
                }
            }

            // Set the `author` field to the resolved names or default to 'Unknown'
            $item['author'] = implode(', ', $authorNames) ?: 'Unknown';

            // Set the item properties
            $item['uri'] = self::URI . '/' . $story['slug'];
            $item['title'] = $story['content']['headline'];
            $item['timestamp'] = strtotime($story['content']['article_date']);
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
