<?php
class HidiveBridge extends BridgeAbstract {
    const NAME = 'HIDIVE News Bridge';
    const URI = 'https://news.hidive.com/';
    const DESCRIPTION = 'Fetches the latest news from HIDIVE.';
    const MAINTAINER = 'Your Name';
    const CACHE_TIMEOUT = 3600; // 1 hour cache

    public function collectData() {
        $apiUrl = 'https://apigw.hidive.com/news/news';

        // Define POST payload
        $postData = json_encode([
            'take' => 9,
            'skip' => 0,
            'filter' => new stdClass()
        ]);

        // Define headers
        $headers = [
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:133.0) Gecko/20100101 Firefox/133.0',
            'Accept: */*',
            'Accept-Language: en-US,en;q=0.5',
            'Content-Type: application/json',
            'Origin: https://news.hidive.com',
            'Referer: https://news.hidive.com/'
        ];

        // Prepare the HTTP options for getContents
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headers),
                'content' => $postData,
                'ignore_errors' => true
            ]
        ];

        // Use getContents for the HTTP request
        $response = getContents($apiUrl, $options);

        if ($response === false) {
            returnServerError('Unable to fetch data from HIDIVE API.');
        }

        // Decode the JSON response
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            returnServerError('Failed to decode JSON: ' . json_last_error_msg());
        }

        // Process each news item
        foreach ($data as $item) {
            $newsItem = [];

            // Clean and format the data
            $excerpt = isset($item['excerpt']) ? ltrim($item['excerpt']) : '';
            $seoUrl = isset($item['seoUrl']) ? 'https://news.hidive.com' . $item['seoUrl'] : '';
            $image = isset($item['image']) ? 'https:' . $item['image'] : '';

            // Create feed item
            $newsItem['uri'] = $seoUrl;
            $newsItem['title'] = $item['title'] ?? '';
            $newsItem['timestamp'] = strtotime($item['releaseDate'] ?? '');

            // Construct content with image and excerpt
            $content = '';
            if ($image) {
                $content .= '<img src="' . htmlspecialchars($image) . '" alt="' . 
                           htmlspecialchars($item['title'] ?? '') . '">';
            }
            $content .= '<p>' . htmlspecialchars($excerpt) . '</p>';

            $newsItem['content'] = $content;

            // Add categories if available
            if (isset($item['categories']) && is_array($item['categories'])) {
                $newsItem['categories'] = $item['categories'];
            }

            // Add author if available
            if (isset($item['author'])) {
                $newsItem['author'] = $item['author'];
            }

            $this->items[] = $newsItem;
        }
    }

    public function getName() {
        return self::NAME;
    }

    public function getURI() {
        return self::URI;
    }

    public function getIcon() {
        return 'https://www.hidive.com/favicon.ico';
    }
}
