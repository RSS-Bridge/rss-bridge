<?php

class MerchantAndMillsBridge extends BridgeAbstract {
    const NAME = 'Merchant and Mills Blog';
    const URI = 'https://merchantandmills.com';
    const DESCRIPTION = 'The latest blog posts from Merchant and Mills.';
    const MAINTAINER = 'caseykulm';
    const CACHE_TIMEOUT = 43200; // 12h
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

        foreach ($html->find('.products .post') as $post) {
            $item = [];

            // Extract image
            $image = $post->find('.post_image img', 0);
            if ($image) {
                // Resolve the relative image URL
                $imageUrl = $image->src;
                if (!str_starts_with($imageUrl, 'http')) {
                    $imageUrl = rtrim(self::URI, '/') . '/' . ltrim($imageUrl, '/');
                }
                $item['image'] = $imageUrl;
            } else {
                $item['image'] = '';
            }

            // Extract title
            $titleLink = $post->find('.post_name a', 0);
            $item['title'] = $titleLink ? trim($titleLink->plaintext) : '';
            $item['uri'] = $titleLink ? self::URI . $titleLink->href : '';

            // Extract date and views
            $dateAndViews = $post->find('.post_date span');
            $item['date'] = isset($dateAndViews[0]) ? trim($dateAndViews[0]->plaintext) : '';
            $item['views'] = isset($dateAndViews[1]) ? trim(str_replace('Viewed:', '', $dateAndViews[1]->plaintext)) : '';

            // Extract description
            $description = $post->find('.post_desc', 0);
            $item['content'] = '';

            // Add the image and description to content
            if (!empty($item['image'])) {
                $item['content'] .= '<div style="text-align: center; max-width: 100%; overflow: hidden;">'
                    . '<img src="' . $item['image'] . '" alt="' . htmlspecialchars($titleLink->plaintext ?? '') . '" '
                    . 'style="max-width: 100%; height: auto; max-height: 700px;" />'
                    . '</div><br />';
            }
            if ($description) {
                $item['content'] .= trim($description->plaintext);
            }

            // Add item to the feed
            $this->items[] = $item;
        }
    }
}
