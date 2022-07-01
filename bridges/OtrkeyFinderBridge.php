<?php

class OtrkeyFinderBridge extends BridgeAbstract
{
    const MAINTAINER = 'mibe';
    const NAME = 'OtrkeyFinder';
    const URI = 'https://otrkeyfinder.com';
    const URI_TEMPLATE = 'https://otrkeyfinder.com/en/?search=%s&order=&page=%d';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns the newest .otrkey files matching the search criteria.';
    const PARAMETERS = [
        [
            'searchterm' => [
                'name' => 'Search term',
                'exampleValue' => 'Tatort',
                'title' => 'The search term is case-insensitive',
            ],
            'station' => [
                'name' => 'Station name',
                'exampleValue' => 'ARD',
            ],
            'type' => [
                'name' => 'Media type',
                'type' => 'list',
                'values' => [
                    'any' => '',
                    'Detail' => [
                        'HD' => 'HD.avi',
                        'AC3' => 'HD.ac3',
                        'HD &amp; AC3' => 'HD.',
                        'HQ' => 'HQ.avi',
                        'AVI' => 'g.avi',   // 'g.' to exclude HD.avi and HQ.avi (filename always contains 'mpg.')
                        'MP4' => '.mp4',
                    ],
                ],
            ],
            'minTime' => [
                'name' => 'Min. running time',
                'type' => 'number',
                'title' => 'The minimum running time in minutes. The resolution is 5 minutes.',
                'exampleValue' => '90',
                'defaultValue' => '0',
            ],
            'maxTime' => [
                'name' => 'Max. running time',
                'type' => 'number',
                'title' => 'The maximum running time in minutes. The resolution is 5 minutes.',
                'exampleValue' => '120',
                'defaultValue' => '0',
            ],
            'pages' => [
                'name' => 'Number of pages',
                'type' => 'number',
                'title' => 'Specifies the number of pages to fetch. Increase this value if you get an empty feed.',
                'exampleValue' => '5',
                'defaultValue' => '5',
            ],
        ]
    ];
    // Example: Terminator_20.04.13_02-25_sf2_100_TVOON_DE.mpg.avi.otrkey
    // The first group is the running time in minutes
    const FILENAME_REGEX = '/_(\d+)_TVOON_DE\.mpg\..+\.otrkey/';
    // year.month.day_hour-minute with leading zeros
    const TIME_REGEX = '/\d{2}\.\d{2}\.\d{2}_\d{2}-\d{2}/';
    const CONTENT_TEMPLATE = '<ul>%s</ul>';
    const MIRROR_TEMPLATE = '<li><a href="https://otrkeyfinder.com%s">%s</a></li>';

    public function collectData()
    {
        $pages = $this->getInput('pages');

        for ($page = 1; $page <= $pages; $page++) {
            $uri = $this->buildUri($page);

            $html = getSimpleHTMLDOMCached($uri, self::CACHE_TIMEOUT);

            $keys = $html->find('div.otrkey');

            foreach ($keys as $key) {
                $temp = $this->buildItem($key);

                if ($temp != null) {
                    $this->items[] = $temp;
                }
            }

            // Sleep for 0.5 seconds to don't hammer the server.
            usleep(500000);
        }
    }

    private function buildUri($page)
    {
        $searchterm = $this->getInput('searchterm');
        $station = $this->getInput('station');
        $type = $this->getInput('type');

        // Combine all three parts to a search query by separating them with white space
        $search = implode(' ', [$searchterm, $station, $type]);
        $search = trim($search);
        $search = urlencode($search);

        return sprintf(self::URI_TEMPLATE, $search, $page);
    }

    private function buildItem(simple_html_dom_node $node)
    {
        $file = $this->getFilename($node);

        if ($file == null) {
            return null;
        }

        $minTime = $this->getInput('minTime');
        $maxTime = $this->getInput('maxTime');

        // Do we need to check the running time?
        if ($minTime != 0 || $maxTime != 0) {
            if ($maxTime > 0 && $maxTime < $minTime) {
                returnClientError('The minimum running time must be less than the maximum running time.');
            }

            preg_match(self::FILENAME_REGEX, $file, $matches);

            if (!isset($matches[1])) {
                return null;
            }

            $time = (int)$matches[1];

            // Check for minimum running time
            if ($minTime > 0 && $minTime > $time) {
                return null;
            }

            // Check for maximum running time
            if ($maxTime > 0 && $maxTime < $time) {
                return null;
            }
        }

        $item = [];
        $item['title'] = $file;

        // The URI_TEMPLATE for querying the site can be reused here
        $item['uri'] = sprintf(self::URI_TEMPLATE, $file, 1);

        $content = $this->buildContent($node);

        if ($content != null) {
            $item['content'] = $content;
        }

        if (preg_match(self::TIME_REGEX, $file, $matches) === 1) {
            $item['timestamp'] = DateTime::createFromFormat(
                'y.m.d_H-i',
                $matches[0],
                new DateTimeZone('Europe/Berlin')
            )->getTimestamp();
        }

        return $item;
    }

    private function getFilename(simple_html_dom_node $node)
    {
        $file = $node->find('.file', 0);

        if ($file == null) {
            return null;
        }

        // Sometimes there is HTML in the filename - we don't want that.
        // To filter that out, enumerate to the node which contains the text only.
        foreach ($file->nodes as $node) {
            if ($node->nodetype == HDOM_TYPE_TEXT) {
                return trim($node->innertext);
            }
        }

        return null;
    }

    private function buildContent(simple_html_dom_node $node)
    {
        $mirrors = $node->find('div.mirror');
        $list = '';

        // Build list of available mirrors
        foreach ($mirrors as $mirror) {
            $anchor = $mirror->find('a', 0);
            $list .= sprintf(self::MIRROR_TEMPLATE, $anchor->href, $anchor->innertext);
        }

        return sprintf(self::CONTENT_TEMPLATE, $list);
    }
}
