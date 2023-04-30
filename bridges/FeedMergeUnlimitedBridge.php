<?php

class FeedMergeUnlimitedBridge extends FeedExpander
{
    const MAINTAINER = 'dvikan, dhuschde';
    const NAME = 'FeedMergeUnlimited';
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
            'feeds' => [
                'name' => 'Feed urls (Comma separated List)',
                'type' => 'text',
                'required' => true,
                'defaultValue' => ['https://www.example.com/feed1, https://www.example.com/feed2'],
                'exampleValue' => 'https://www.example.com/feed1, https://www.example.com/feed2',
            ],
            'limit' => self::LIMIT,
        ]
    ];

    /**
     * todo: Consider a strategy which produces a shorter feed url
     */
    public function collectData()
    {
        $limit = (int)($this->getInput('limit') ?: 10);
        $feeds = array_filter(array_map('trim', explode(',', $this->getInput('feeds'))));

        foreach ($feeds as $feed) {
            // Fetch all items from the feed
            // todo: consider wrapping this in a try..catch to not let a single feed break the entire bridge?
            $this->collectExpandableDatas($feed);
        }

        // Sort by timestamp descending
        usort($this->items, function ($a, $b) {
            $t1 = $a['timestamp'] ?? $a['uri'] ?? $a['title'];
            $t2 = $b['timestamp'] ?? $b['uri'] ?? $b['title'];
            return $t2 <=> $t1;
        });

        // Remove duplicates by using url as unique key
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
        $this->items = array_slice(array_values($items), 0, $limit);
    }

    public function getIcon()
    {
        return 'https://upload.wikimedia.org/wikipedia/commons/thumb/4/43/Feed-icon.svg/48px-Feed-icon.svg.png';
    }

    public function getName()
    {
        return $this->getInput('feed_name') ?: 'rss-bridge/FeedMergeUnlimited';
    }
}
