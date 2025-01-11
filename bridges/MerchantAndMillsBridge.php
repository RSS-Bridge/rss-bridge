<?php

class MerchantAndMillsBridge extends BridgeAbstract
{
    const NAME = 'Merchant and Mills Blog';
    const URI = 'https://merchantandmills.com';
    const DESCRIPTION = 'The latest blog posts from Merchant and Mills.';
    const MAINTAINER = 'caseykulm';
    const CACHE_TIMEOUT = 43200; // 12h
    const POST_LIMIT = 5; // Maximum number of blog posts to fetch
    const PARAMETERS = [[
        'selected_country_id' => [
            'name' => 'Country',
            'type' => 'list',
            'values' => [
                'European Union' => 0,
                'United Kingdom' => 1,
                'United States' => 2,
                'Other' => 3
            ]
        ]
    ]];

    private function getCountryBlogPath($countryName): string
    {
        if ($countryName === 'European Union') {
            return '/eu/blog';
        }

        if ($countryName === 'United Kingdom') {
            return '/uk/blog';
        }

        if ($countryName === 'United States') {
            return '/us/blog';
        }

        return '/rw/blog';
    }

    public function collectData()
    {
        $selectedCountryKey = $this->getKey('selected_country_id');
        $selectedCountryBlogPath = $this->getCountryBlogPath($selectedCountryKey);
        $url = self::URI . $selectedCountryBlogPath;
        $html = getSimpleHTMLDOM($url)
            or returnServerError('Could not request ' . $url);

        // Limit processing to POST_LIMIT blog posts
        $counter = 0;
        foreach ($html->find('.products .post') as $post) {
            if ($counter >= self::POST_LIMIT) {
                break; // Stop when the limit is reached
            }

            $item = [];

            // Extract title and URI
            $titleLink = $post->find('.post_name a', 0);
            $item['title'] = $titleLink ? trim($titleLink->plaintext) : 'No title';
            $item['uri'] = $titleLink ? self::URI . $titleLink->href : '';

            // Extract date
            $dateElement = $post->find('.post_date span', 0);
            $item['timestamp'] = $dateElement ? strtotime(trim($dateElement->plaintext)) : null;

            // Extract and fetch content
            if ($item['uri']) {
                $item['content'] = $this->getPostContent($item['uri']);
            } else {
                $item['content'] = 'No content available.';
            }

            $this->items[] = $item;
            $counter++;
        }
    }

    /**
     * Fetch and parse the content of a single blog post.
     *
     * @param string $url The URL of the single blog post.
     * @return string The HTML content of the post.
     */
    private function getPostContent(string $url): string
    {
        try {
            $postHtml = getSimpleHTMLDOM($url)
                or returnServerError('Could not fetch content from ' . $url);

            $contentElement = $postHtml->find('.box.w-blog-widget-post-description', 0);

            // Adjust relative URLs for images and add scaling style
            if ($contentElement) {
                foreach ($contentElement->find('img') as $img) {
                    $src = $img->src;
                    if (strpos($src, 'http') !== 0) { // If it's a relative path
                        $img->src = self::URI . $src;
                    }

                    // Add inline styles for proper scaling
                    $img->style = 'max-width: 100%; height: auto; display: block; margin: auto;';
                }
            }

            return $contentElement ? $contentElement->innertext : 'Content not found.';
        } catch (Exception $e) {
            return 'Failed to fetch content: ' . $e->getMessage();
        }
    }

    public function getIcon(): string
    {
        return 'https://merchantandmills.com/uk/themes/theme-1/icons/apple-icon-57x57.png?6763';
    }
}
