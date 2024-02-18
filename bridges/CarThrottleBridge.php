<?php

class CarThrottleBridge extends BridgeAbstract
{
    const NAME = 'Car Throttle';
    const URI = 'https://www.carthrottle.com/';
    const DESCRIPTION = 'Get the latest car-related news from Car Throttle.';
    const MAINTAINER = 't0stiman';
    const DONATION_URI = 'https://ko-fi.com/tostiman';

    const PARAMETERS = [
        'Show articles from these categories:' => [
            'news' => [
                'name' => 'news',
                'type' => 'checkbox'
            ],
            'reviews' => [
                'name' => 'reviews',
                'type' => 'checkbox'
            ],
            'features' => [
                'name' => 'features',
                'type' => 'checkbox'
            ],
            'videos' => [
                'name' => 'videos',
                'type' => 'checkbox'
            ],
            'gaming' => [
                'name' => 'gaming',
                'type' => 'checkbox'
            ]
        ]
    ];

    public function collectData()
    {
        $this->items = [];

        $this->handleCategory('news');
        $this->handleCategory('reviews');
        $this->handleCategory('features');
        $this->handleCategory2('videos', 'video');
        $this->handleCategory('gaming');
    }

    private function handleCategory($category)
    {
        if ($this->getInput($category)) {
            $this->getArticles($category);
        }
    }

    private function handleCategory2($categoryParameter, $categoryURLname)
    {
        if ($this->getInput($categoryParameter)) {
            $this->getArticles($categoryURLname);
        }
    }

    private function getArticles($category)
    {
        $categoryPage = getSimpleHTMLDOMCached(self::URI . $category);

        //for each post
        foreach ($categoryPage->find('div.cmg-card') as $post) {
            $item = [];

            $titleElement = $post->find('div.title a')[0];
            $post_uri = self::URI . $titleElement->getAttribute('href');

            if (!isset($post_uri) || $post_uri == '') {
                continue;
            }

            $item['uri'] = $post_uri;
            $item['title'] = $titleElement->innertext;

            $articlePage = getSimpleHTMLDOMCached($item['uri']);

            $item['author'] = $this->parseAuthor($articlePage);

            $articleElement = $articlePage->find('article')[0];

            //remove ads
            foreach ($articleElement->find('aside') as $ad) {
                $ad->outertext = '';
            }

            $summary = $articleElement->find('div.summary')[0];

            //remove header so we are left with the article content
            foreach ($articleElement->find('header') as $found) {
                $found->outertext = '';
            }

            //remove comments (registering on carthrottle.com is impossible so the comment sections are empty anyway)
            foreach ($articleElement->find('#lbs-comments') as $found) {
                $found->outertext = '';
            }

            //these are supposed to be hidden
            foreach ($articleElement->find('.visually-hidden') as $found) {
                $found->outertext = '';
            }

            $item['content'] = $summary . $articleElement;

            array_push($this->items, $item);
        }
    }

    private function parseAuthor($articlePage)
    {
        $authorDivs = $articlePage->find('div address');
        if (!$authorDivs) {
            return '';
        }

        $a = $authorDivs[0]->find('a');
        if ($a) {
            return $a->innertext;
        }

        return $authorDivs[0]->innertext;
    }
}
