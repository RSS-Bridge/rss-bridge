<?php

class DuvarOrgBridge extends BridgeAbstract
{
    const NAME = 'Duvar.org - Haberler';
    const MAINTAINER = 'yourname';
    const URI = 'https://duvar.org';
    const DESCRIPTION = 'Returns the latest articles from Duvar.org - News from Turkey and the world';
    const CACHE_TIMEOUT = 3600; // 60min

    const PARAMETERS = [[
        'postcount' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => true,
            'title' => 'Maximum number of items to return',
            'defaultValue' => 20,
        ],
        'urlsuffix' => [
            'name' => 'URL Suffix',
            'type' => 'list',
            'title' => 'Suffix for the URL to scrape a specific section',
            'defaultValue' => 'Main',
            'values' => [
                'Main' => '',
                'Balanced' => '/uyumlu',
                'Protest' => '/muhalif',
                'Center' => '/merkez',
                'Alternative' => '/alternatif',
                'Global' => '/global',
            ],
        ],
    ]];

    public function collectData()
    {
        $postCount = $this->getInput('postcount');
        $urlSuffix = $this->getInput('urlsuffix');
        $url = self::URI . $urlSuffix;
        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('article.news-item') as $data) {
            if ($data === null) {
                continue;
            }

            try {
                $item = [];
                $linkElement = $data->find('h2.news-title a', 0);
                $titleElement = $data->find('h2.news-title a', 0);
                $timestampElement = $data->find('time.meta-tag.date-tag', 0);
                $contentElement = $data->find('div.news-description', 0);

                if ($linkElement) {
                    $item['uri'] = $linkElement->getAttribute('href');
                } else {
                    continue;
                }
                if ($titleElement) {
                    $item['title'] = trim($titleElement->plaintext);
                } else {
                    continue;
                }
                if ($timestampElement) {
                    $item['timestamp'] = strtotime($timestampElement->plaintext);
                } else {
                    $item['timestamp'] = time();
                }
                if ($contentElement) {
                    $item['content'] = trim($contentElement->plaintext);
                } else {
                    $item['content'] = '';
                }
                $item['uid'] = hash('sha256', $item['title']);

                $this->items[] = $item;

                if (count($this->items) >= $postCount) {
                    break;
                }
            } catch (Exception $e) {
                continue;
            }
        }
    }
}