<?php

class HarvardBusinessReviewBridge extends BridgeAbstract
{
    const NAME = 'Harvard Business Review - The Latest';
    const MAINTAINER = 'yourname';
    const URI = 'https://hbr.org';
    const DESCRIPTION = 'Returns the latest articles from Harvard Business Review';
    const CACHE_TIMEOUT = 3600; // 60min

    const PARAMETERS = [[
        'postcount' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => true,
            'title' => 'Maximum number of items to return',
            'defaultValue' => 6, //More requires clicking button "Load more"
        ],
    ]];

    public function collectData()
    {
        $url = self::URI . '/the-latest';
        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('li.stream-entry') as $data) {
            // Skip if $data is null
            if ($data === null) {
                continue;
            }

            try {
                // Skip entries containing the text 'stream-ad-container'
                if ($data->innertext !== null && strpos($data->innertext, 'stream-ad-container') !== false) {
                    continue;
                }

                // Skip entries with class 'sponsored'
                if ($data->hasClass('sponsored')) {
                    continue;
                }

                $item = [];
                $linkElement = $data->find('a', 0);
                $titleElement = $data->find('h3.hed a', 0);
                $authorElement = $data->find('ul.byline-list li', 0);
                $timestampElement = $data->find('li.pubdate time', 0);
                $contentElement = $data->find('div.dek', 0);

                if ($linkElement) {
                    $item['uri'] = self::URI . $linkElement->getAttribute('href');
                } else {
                    continue; // Skip this entry if no link is found
                }
                if ($titleElement) {
                    $item['title'] = trim($titleElement->plaintext);
                } else {
                    continue; // Skip this entry if no title is found
                }
                if ($authorElement) {
                    $item['author'] = trim($authorElement->plaintext);
                } else {
                    $item['author'] = 'Unknown'; // Default value if author is missing
                }
                if ($timestampElement) {
                    $item['timestamp'] = strtotime($timestampElement->plaintext);
                } else {
                    $item['timestamp'] = time(); // Default to current time if timestamp is missing
                }
                if ($contentElement) {
                    $item['content'] = trim($contentElement->plaintext);
                } else {
                    $item['content'] = ''; // Default to empty string if content is missing
                }
                $item['uid'] = hash('sha256', $item['title']);

                $this->items[] = $item;

                if (count($this->items) >= $this->getInput('postcount')) {
                    break;
                }
            } catch (Exception $e) {
                // Log the error if necessary
                continue; // Skip to the next iteration on error
            }
        }
    }
}