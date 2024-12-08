<?php

class NACSouthGermanyMediaLibraryBridge extends BridgeAbstract
{
    private const BASE_URI = 'https://www.nak-sued.de';

    const NAME = 'NAK Süd Mediathek';
    const DESCRIPTION = 'RSS Feed für die Runkfunkbeiträge der NAK Süd auf Bayern 2 und SWR 1.
         (Technical note: This bridge might not work on certain server instances because of blacklisted IP ranges to the website.)';
    const URI = self::BASE_URI . '/mediathek';
    const MAINTAINER = 'R3dError';
    const CACHE_TIMEOUT = 7200;

    private const BAYERN2_ROOT_URI = self::BASE_URI . '/mediathek/rundfunksendungen-auf-bayern-2/aktuelle-sendungen';
    private const SWR1_ROOT_URI = self::BASE_URI . '/mediathek/rundfunksendungen-auf-swr1/aktuelle-sendungen';

    private const MONTHS = [
        'Januar' => 1,
        'Februar' => 2,
        'März' => 3,
        'April' => 4,
        'Mai' => 5,
        'Juni' => 6,
        'Juli' => 7,
        'August' => 8,
        'September' => 9,
        'Oktober' => 10,
        'November' => 11,
        'Dezember' => 12,
    ];

    public function getIcon()
    {
        return 'https://nak-sued.de/static/themes/sued/images/logo.png';
    }

    private static function parseTimestamp($title)
    {
        if (preg_match('/([0-9]+)\.\s*([^\s]+)\s*([0-9]+)/', $title, $matches)) {
            $day = $matches[1];
            $month = self::MONTHS[$matches[2]];
            $year = $matches[3];
            return $year . '-' . $month . '-' . $day;
        } else {
            return '';
        }
    }

    private static function collectDataForSWR1($parent, $item)
    {
        # Find link
        $sourceURI = $parent->find('a', 1)->href;
        $item['enclosures'] = [self::BASE_URI . $sourceURI];

        # Add time to timestamp
        $item['timestamp'] .= ' 07:27';

        # Find author
        if (preg_match('/\((.*?)\)/', html_entity_decode($item['content']), $matches)) {
            $item['author'] = $matches[1];
        }

        return $item;
    }

    private static function collectDataForBayern2($parent, $item)
    {
        # Find link
        $relativeURICode = $parent->find('a', 0)->onclick;
        if (preg_match('/window\.open\(\'([^\']*)\'/', $relativeURICode, $matches)) {
            $playerDom = getSimpleHTMLDOMCached(self::BASE_URI . $matches[1]);
            $sourceURI = $playerDom->find('source', 0)->src;
            $item['enclosures'] = [self::BASE_URI . $sourceURI];
        }

        # Add time to timestamp
        $item['timestamp'] .= ' 06:45';

        return $item;
    }

    private function collectDataInList($pageURI, $customizeItemCall)
    {
        $page = getSimpleHTMLDOM($pageURI);

        foreach ($page->find('div.flex-columns.entry') as $parent) {
            # Find title
            $title = trim($parent->find('h2')[0]->innertext);

            # Find content
            $contentBlock = $parent->find('div')[2];
            $content = '';
            foreach ($contentBlock->find('li,p') as $li) {
                $content .= '<p>' . $li->plaintext . '</p>';
            }

            $item = [
                'title' => $title,
                'content' => $content,
                'timestamp' => self::parseTimestamp($title),
            ];
            $this->items[] = $customizeItemCall($parent, $item);
        }
    }

    private function collectDataFromAllPages($rootURI, $customizeItemCall)
    {
        $rootPage = getSimpleHTMLDOM($rootURI);
        $pages = $rootPage->find('div.flex-columns.inner_filter', 0);
        foreach ($pages->find('a') as $page) {
            self::collectDataInList($page->href, [$this, $customizeItemCall]);
        }
    }

    public function collectData()
    {
        # Collect items
        self::collectDataFromAllPages(self::BAYERN2_ROOT_URI, 'collectDataForBayern2');
        self::collectDataFromAllPages(self::SWR1_ROOT_URI, 'collectDataForSWR1');

        # Sort items by decreasing timestamp
        usort($this->items, function ($a, $b) {
            return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
        });
    }
}
