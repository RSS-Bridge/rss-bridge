<?php

class NakSuedMediathekBridge extends BridgeAbstract
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
        $dom1 = getSimpleHTMLDOM(self::SWR1_ROOT_URI);
        $dom2 = getSimpleHTMLDOM(self::BAYERN2_ROOT_URI);
        $description = $dom1->find('div.csc-default', 0)->plaintext . ' ' . $dom2->find('div.csc-default', 0)->plaintext;
        return $description;
	}

    private function parseTimeStamp($title) {
        if (preg_match('/([0-9]+)\.\s*([a-zA-Z]+)\s*([0-9]+)/', $title, $matches)) {
            $day = $matches[1];
            $month = self::MONTHS[$matches[2]];
            $year = $matches[3];
            return $year . '-' . $month . '-' . $day;
        } else {
            return '';
        }
    }

    private function collectDataForSWR1($uri) {
        $dom = getSimpleHTMLDOM(self::URI . $uri);

        foreach ($dom->find('div.grids') as $div) {
            $header = $div->find('h2', 0);

            # Parse description
            $descriptionBlock = $div->find('ul.contentlist', 0);
            $description = '';
            $firstIteration = TRUE;
            foreach ($descriptionBlock->find('li') as $li) {
                if (!$firstIteration) {
                    $description .= ", ";
                }
                $description .= $li->plaintext;
                $firstIteration = FALSE;
            }

            # Parse link
            $source = $div->find('a', 1);
            
            # Parse author
            if (preg_match('/(.*?)\((.*?)\)/', $description, $matches)) {
                $description = $matches[1];
                $author = $matches[2];
            } else {
                $author = '';
            }

            # TODO: add uri, see https://www.nak-sued.de/meldungen/news/hoerfunksendung-am-27-august-2023-auf-bayern-2/

            $this->items[] = [
                'title' => $header->plaintext,
                'author' => $author,
                'content' => $description,
                'enclosures' => [self::URI . $source->href],
                'timestamp' => self::parseTimeStamp($header->plaintext) . ' 07:27',
            ];
        }
    }

    private function collectDataForBayern2($uri) {
        $dom = getSimpleHTMLDOM(self::URI . $uri);

        foreach ($dom->find('div.grids') as $div) {
            $header = $div->find('h2', 0);

            # Parse description
            $descriptionBlock = $div->find('ul.contentlist', 0);
            $description = '';
            $firstIteration = TRUE;
            foreach ($descriptionBlock->find('li') as $li) {
                if (!$firstIteration) {
                    $description .= ", ";
                }
                $description .= $li->plaintext;
                $firstIteration = FALSE;
            }

            # Parse link
            $a = $div->find('a', 0);
            $playerDom = getSimpleHTMLDOMCached(self::URI . $a->href);
            $audio = $playerDom->find('audio', 0);
            $source = $audio->find('source', 0);

            # TODO: add uri, see https://www.nak-sued.de/meldungen/news/hoerfunksendung-am-27-august-2023-auf-bayern-2/

            $this->items[] = [
                'title' => $header->plaintext,
                'content' => $description,
                'enclosures' => [self::URI . $source->src],
                'timestamp' => self::parseTimeStamp($header->plaintext) . '06:45',
            ];
        }
    }

    public function collectData() {
        # TODO: get description for entire feed

        $dom = getSimpleHTMLDOM(self::BAYERN2_ROOT_URI);
        $pages = $dom->find('div#tabmenu', 0);
        foreach ($pages->find('a') as $page) {
            self::collectDataForBayern2($page->href);
        }

        $dom = getSimpleHTMLDOM(self::SWR1_ROOT_URI);
        $pages = $dom->find('div#tabmenu', 0);
        foreach ($pages->find('a') as $page) {
            self::collectDataForSWR1($page->href);
        }
    }
}
