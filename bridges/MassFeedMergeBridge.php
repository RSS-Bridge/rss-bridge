<?php

class MassFeedMergeBridge extends FeedExpander
{
    const MAINTAINER = 'pastram_i';
    const NAME = 'MassFeedMerge';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge';
    const DESCRIPTION = <<<'TEXT'
An extention of FeedMergeBridge (by dvikan) - allowing more feeds. \r\n This bridge merges two or more feeds into a single feed. Max 10 items are fetched from each feed. \r\n Feeds should be space delimited. \r\n Extremely long requests should change large_client_header_buffers in nginx.conf
TEXT;

    const PARAMETERS = [
        [
            'feed_name' => [
                'name' => 'Feed name',
                'type' => 'text',
                'exampleValue' => 'rss-bridge/MassFeedMerge',
            ],
            'feed' => [
                'name' => 'Feed urls',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'https://feed1.com/feed https://feed2.com/rss ...'
            ],

            'limit' => self::LIMIT,
        ]
    ];

    public function collectData()
    {
        $limit = (int)($this->getInput('limit') ?: 10);
        $feeds = explode (" ", $this->getInput('feed'));

        foreach ($feeds as $feed) {
            // Fetch all items from the feed
            $this->collectExpandableDatas($feed);
        }

        // Sort by timestamp descending
        usort($this->items, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        // Remove duplicates
        $items = [];
        foreach ($this->items as $item) {
            $index = $item['uri'] ?? null;
            if ($index) {
                // Overwrite duplicates
                $items[$index] = $item;
            } else {
                $items[] = $item;
            }
        }

        // Grab the first $limit items
        $this->items = array_slice(array_values($items), 0, $limit);
    }

    public function getIcon()
    {
        return 'https://cdn.jsdelivr.net/npm/famfamfam-silk@1.0.0/dist/png/folder_feed.png';
    }

    public function getName()
    {
        return $this->getInput('feed_name') ?: 'rss-bridge/MassFeedMerge';
    }
}
