<?php

class NacSouthMediaLibraryBridge extends BridgeAbstract
{
    const NAME = 'NAK Süd Mediathek (https://www.nak-sued.de/mediathek)';
    const URI = 'https://www.nak-sued.de';
    const CACHE_TIMEOUT = 7200;

    const BAYERN2_ROOT_URI = self::URI . '/mediathek/rundfunksendungen-auf-bayern-2/aktuelle-sendungen';
    const SWR1_ROOT_URI = self::URI . '/mediathek/rundfunksendungen-auf-swr1/aktuelle-sendungen';

    const MONTHS = [
        'Januar' => 1,
        'Februar'=> 2,
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

    public function getIcon() {
		return self::URI . '/typo3conf/ext/nak_naksued_de/Resources/Public/Images/favicon.ico';
	}

    public function getDescription() {
        $swr1Dom = getSimpleHTMLDOM(self::SWR1_ROOT_URI);
        $bayern2Dom = getSimpleHTMLDOM(self::BAYERN2_ROOT_URI);
        $description = $swr1Dom->find('div.csc-default', 0)->plaintext . ' ' . $bayern2Dom->find('div.csc-default', 0)->plaintext;
        return $description;
	}

    private function parseTimestamp($title) {
        if (preg_match('/([0-9]+)\.\s*([^\s]+)\s*([0-9]+)/', $title, $matches)) {
            $day = $matches[1];
            $month = self::MONTHS[$matches[2]];
            $year = $matches[3];
            return $year . '-' . $month . '-' . $day;
        } else {
            return '';
        }
    }

    private function collectDataForSWR1($parent, $item) {
        # Parse link
        $sourceURI = $parent->find('a', 1)->href;
        $item['enclosures'] = [self::URI . $sourceURI];

        # Add time to timestamp
        $item['timestamp'] .= ' 07:27';

        # Find author
        if (preg_match('/<p>(.*?)\((.*?)\)\s*?<\/p>/', html_entity_decode($item['content']), $matches)) {
            $item['content'] = '<p>' . trim(trim($matches[1]), '„“"') . '</p>';
            $item['author'] = $matches[2];
        }

        # TODO: add uri, see https://www.nak-sued.de/meldungen/news/hoerfunksendung-am-27-august-2023-auf-bayern-2/

        return $item;
    }

    private function collectDataForBayern2($parent, $item) {
        # Find link
        $playerDom = getSimpleHTMLDOMCached(self::URI . $parent->find('a', 0)->href);
        $sourceURI = $playerDom->find('source', 0)->src;
        $item['enclosures'] = [self::URI . $sourceURI];

        # Add time to timestamp
        $item['timestamp'] .= ' 06:45';

        # TODO: add uri, see https://www.nak-sued.de/meldungen/news/hoerfunksendung-am-27-august-2023-auf-bayern-2/

        return $item;
    }

    private function collectDataInList($pageURI, $customizeItemCall) {
        $page = getSimpleHTMLDOM(self::URI . $pageURI);

        foreach ($page->find('div.grids') as $parent) {
            # Find title
            $title = $parent->find('h2', 0)->plaintext;

            # Find content
            $contentBlock = $parent->find('ul.contentlist', 0);
            $content = '';
            foreach ($contentBlock->find('li') as $li) {
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

    private function collectDataFromAllPages($rootURI, $customizeItemCall) {
        $rootPage = getSimpleHTMLDOM($rootURI);
        $pages = $rootPage->find('div#tabmenu', 0);
        foreach ($pages->find('a') as $page) {
            self::collectDataInList($page->href, [$this, $customizeItemCall]);
        }
    }

    public function collectData() {
        self::collectDataFromAllPages(self::BAYERN2_ROOT_URI, 'collectDataForBayern2');
        self::collectDataFromAllPages(self::SWR1_ROOT_URI, 'collectDataForSWR1');

        # Sort items by decreasing timestamp
        usort($this->items, function ($a, $b) {
            return strtotime($b["timestamp"]) <=> strtotime($a["timestamp"]);
        });
    }
}
