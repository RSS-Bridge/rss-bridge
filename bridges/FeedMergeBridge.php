<?php

class FeedMergeBridge extends FeedExpander
{
    const MAINTAINER = 'dvikan';
    const NAME = 'FeedMerge';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge';
    const DESCRIPTION = <<<'TEXT'
This bridge merges two or more feeds into a single feed. Max 10 items are fetched from each feed.
TEXT;

    const PARAMETERS = [
        [
            'feed_name' => [
                'name' => 'Feed name',
                'type' => 'text',
                'exampleValue' => 'rss-bridge/FeedMerger',
            ],
            'feed_1' => [
                'name' => 'Feed url',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'https://lorem-rss.herokuapp.com/feed?unit=day'
            ],
            'feed_2' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_3' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_4' => ['name' => 'Feed url', 'type' => 'text'],
            'feed_5' => ['name' => 'Feed url', 'type' => 'text'],

            'limit' => self::LIMIT,
        ]
    ];

    public function collectData()
    {
        $limit = (int)($this->getInput('limit') ?: 10);
        $feeds = [
            $this->getInput('feed_1'),
            $this->getInput('feed_2'),
            $this->getInput('feed_3'),
            $this->getInput('feed_4'),
            $this->getInput('feed_5'),
        ];

        // Remove empty values
        $feeds = array_filter($feeds);

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
        return $this->getInput('feed_name') ?: 'rss-bridge/FeedMerger';
    }
}
