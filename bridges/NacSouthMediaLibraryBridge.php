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

    private function parseTimeStamp($title) {
        if (preg_match('/([0-9]+)\.\s*([^\s]+)\s*([0-9]+)/', $title, $matches)) {
            $day = $matches[1];
            $month = self::MONTHS[$matches[2]];
            $year = $matches[3];
            return $year . '-' . $month . '-' . $day;
        } else {
            return '';
        }
    }

    private function collectDataForSWR1($parent, $title, $content) {
        # Parse link
        $sourceURI = $parent->find('a', 1)->href;

        # Parse author
        if (preg_match('/<p>(.*?)\((.*?)\)</p>/', $content, $matches)) {
            $content = '<p>' . trim(html_entity_decode(trim($matches[1])), '„“"') . '</p>';
            $author = $matches[2];
        } else {
            $author = '';
        }

        # TODO: add uri, see https://www.nak-sued.de/meldungen/news/hoerfunksendung-am-27-august-2023-auf-bayern-2/

        $this->items[] = [
            'title' => $title,
            'author' => $author,
            'content' => $content,
            'enclosures' => [self::URI . $sourceURI],
            'timestamp' => self::parseTimeStamp($title) . ' 07:27',
        ];
    }

    private function collectDataForBayern2($parent, $title, $content) {
        # Parse link
        $playerDom = getSimpleHTMLDOMCached(self::URI . $parent->find('a', 0)->href);
        $sourceURI = $playerDom->find('source', 0)->src;

        # TODO: add uri, see https://www.nak-sued.de/meldungen/news/hoerfunksendung-am-27-august-2023-auf-bayern-2/

        $this->items[] = [
            'title' => $title,
            'content' => $content,
            'enclosures' => [self::URI . $sourceURI],
            'timestamp' => self::parseTimeStamp($title) . ' 06:45',
        ];
    }

    private function collectDataInList($uri, $finalizeItemCall) {
        $dom = getSimpleHTMLDOM(self::URI . $uri);

        foreach ($dom->find('div.grids') as $div) {
            # Find title
            $header = $div->find('h2', 0);

            # Find content
            $contentBlock = $div->find('ul.contentlist', 0);
            $content = '';
            foreach ($contentBlock->find('li') as $li) {
                $content .= '<p>' . $li->plaintext . '</p>';
            }

            $finalizeItemCall($div, $header->plaintext, $content);
        }
    }

    private function collectDataFromAllPages($rootURI, $finalizeItemMethodName) {
        $rootPage = getSimpleHTMLDOM(self::BAYERN2_ROOT_URI);
        $pages = $rootPage->find('div#tabmenu', 0);
        foreach ($pages->find('a') as $page) {
            self::collectDataInList($page->href, [$this, $finalizeItemMethodName]);
        }
    }

    public function collectData() {
        # TODO: get description for entire feed

        self::collectDataFromAllPages(self::BAYERN2_ROOT_URI, 'collectDataForBayern2');
        self::collectDataFromAllPages(self::SWR1_ROOT_URI, 'collectDataForSWR1');

        # Sort items by decreasing timestamp
        usort($this->items, function ($a, $b) {
            return strtotime($b["timestamp"]) <=> strtotime($a["timestamp"]);
        });
    }
}
