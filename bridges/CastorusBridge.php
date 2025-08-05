<?php

class CastorusBridge extends BridgeAbstract
{
    const MAINTAINER = 'logmanoriginal';
    const NAME = 'Castorus Bridge';
    const URI = 'https://www.castorus.com';
    const CACHE_TIMEOUT = 600; // 10min
    const DESCRIPTION = 'Returns the latest changes';

    const PARAMETERS = [
        'Get latest changes' => [],
        'Get latest changes via ZIP code' => [
            'zip' => [
                'name' => 'ZIP code',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '7',
                'title' => 'Insert ZIP code (complete or partial). e.g: 78125 OR 781 OR 7'
            ]
        ],
        'Get latest changes via city name' => [
            'city' => [
                'name' => 'City name',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'Paris',
                'title' => 'Insert city name (complete or partial). e.g: Paris OR Par OR P'
            ]
        ]
    ];

    // Extracts the title from an actitiy
    private function extractActivityTitle($activity)
    {
        $title = $activity->find('a', 0);

        if (!$title) {
            throwServerException('Cannot find title!');
        }

        return trim($title->plaintext);
    }

    // Extracts the url from an actitiy
    private function extractActivityUrl($activity)
    {
        $url = $activity->find('a', 0);

        if (!$url) {
            throwServerException('Cannot find url!');
        }

        return self::URI . $url->href;
    }

    // Extracts the time from an activity
    private function extractActivityTime($activity)
    {
        // Unfortunately the time is part of the parent node,
        // so we have to clear all child nodes first
        $nodes = $activity->find('*');

        if (!$nodes) {
            throwServerException('Cannot find nodes!');
        }

        foreach ($nodes as $node) {
            $node->outertext = '';
        }

        return strtotime($activity->innertext);
    }

    // Extracts the price change
    private function extractActivityPrice($activity)
    {
        $price = $activity->find('span', 1);

        if (!$price) {
            throwServerException('Cannot find price!');
        }

        return $price->innertext;
    }

    public function collectData()
    {
        $zip_filter = trim($this->getInput('zip'));
        $city_filter = trim($this->getInput('city'));

        $html = getSimpleHTMLDOM(self::URI);

        if (!$html) {
            throwServerException('Could not load data from ' . self::URI . '!');
        }

        $activities = $html->find('div#activite > li');

        if (!$activities) {
            throwServerException('Failed to find activities!');
        }

        foreach ($activities as $activity) {
            $item = [];

            $item['title'] = $this->extractActivityTitle($activity);
            $item['uri'] = $this->extractActivityUrl($activity);
            $item['timestamp'] = $this->extractActivityTime($activity);
            $item['content'] = '<a href="'
            . $item['uri']
            . '">'
            . $item['title']
            . '</a><br><p>'
            . $this->extractActivityPrice($activity)
            . '</p>';

            if (
                isset($zip_filter)
                && !(substr($item['title'], 0, strlen($zip_filter)) === $zip_filter)
            ) {
                continue; // Skip this item
            }

            if (
                isset($city_filter)
                && !(substr($item['title'], strpos($item['title'], ' ') + 1, strlen($city_filter)) === $city_filter)
            ) {
                continue; // Skip this item
            }

            $this->items[] = $item;
        }
    }
}
