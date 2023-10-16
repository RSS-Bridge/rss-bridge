<?php

class CssSelectorFeedExpanderBridge extends CssSelectorBridge
{
    const MAINTAINER = 'ORelio';
    const NAME = 'CSS Selector Feed Expander';
    const URI = 'https://github.com/RSS-Bridge/rss-bridge/';
    const DESCRIPTION = 'Expand any site RSS feed using CSS selectors (Advanced Users)';
    const PARAMETERS = [
        [
            'feed' => [
                'name' => 'Feed: URL of truncated RSS feed',
                'exampleValue' => 'https://example.com/feed.xml',
                'required' => true
            ],
            'content_selector' => [
                'name' => 'Selector for each article content',
                'title' => <<<EOT
                    This bridge works using CSS selectors, e.g. "div.article" will match <div class="article">.
                    Everything inside that element becomes feed item content.
                    EOT,
                'exampleValue' => 'article.content',
                'required' => true
            ],
            'content_cleanup' => [
                'name' => '[Optional] Content cleanup: List of items to remove',
                'title' => 'Selector for unnecessary elements to remove inside article contents.',
                'exampleValue' => 'div.ads, div.comments',
            ],
            'dont_expand_metadata' => [
                'name' => '[Optional] Don\'t expand metadata',
                'title' => "This bridge will attempt to fill missing fields using metadata from the webpage.\nCheck to disable.",
                'type' => 'checkbox',
            ],
            'discard_thumbnail' => [
                'name' => '[Optional] Discard thumbnail set by site author',
                'title' => 'Some sites set their logo as thumbnail for every article. Use this option to discard it.',
                'type' => 'checkbox',
            ],
            'limit' => self::LIMIT
        ]
    ];

    public function collectData()
    {
        $url = $this->getInput('feed');
        $content_selector = $this->getInput('content_selector');
        $content_cleanup = $this->getInput('content_cleanup');
        $dont_expand_metadata = $this->getInput('dont_expand_metadata');
        $discard_thumbnail = $this->getInput('discard_thumbnail');
        $limit = $this->getInput('limit');

        $feedParser = new FeedParser();
        $xml = getContents($url);
        $source_feed = $feedParser->parseFeed($xml);
        $items = $source_feed['items'];

        // Map Homepage URL (Default: Root page)
        if (isset($source_feed['uri'])) {
            $this->homepageUrl = $source_feed['uri'];
        } else {
            $this->homepageUrl = urljoin($url, '/');
        }

        // Map Feed Name (Default: Domain name)
        if (isset($source_feed['title'])) {
            $this->feedName = $source_feed['title'];
        } else {
            $this->feedName = explode('/', urljoin($url, '/'))[2];
        }

        // Apply item limit (Default: Global limit)
        if ($limit > 0) {
            $items = array_slice($items, 0, $limit);
        }

        // Expand feed items (CssSelectorBridge)
        foreach ($items as $item_from_feed) {
            $item_expanded = $this->expandEntryWithSelector(
                $item_from_feed['uri'],
                $content_selector,
                $content_cleanup
            );

            if ($dont_expand_metadata) {
                // Take feed item, only replace content from expanded data
                $content = $item_expanded['content'];
                $item_expanded = $item_from_feed;
                $item_expanded['content'] = $content;
            } else {
                // Take expanded item, but give priority to metadata already in source item
                foreach ($item_from_feed as $field => $val) {
                    if ($field !== 'content' && !empty($val)) {
                        $item_expanded[$field] = $val;
                    }
                }
            }

            if ($discard_thumbnail && isset($item_expanded['enclosures'])) {
                unset($item_expanded['enclosures']);
            }

            $this->items[] = $item_expanded;
        }
    }
}
