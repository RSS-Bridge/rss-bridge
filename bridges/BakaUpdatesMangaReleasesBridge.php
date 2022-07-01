<?php

class BakaUpdatesMangaReleasesBridge extends BridgeAbstract
{
    const NAME = 'Baka Updates Manga Releases';
    const URI = 'https://www.mangaupdates.com/';
    const DESCRIPTION = 'Get the latest series releases';
    const MAINTAINER = 'fulmeek, KamaleiZestri';
    const PARAMETERS = [
        'By series' => [
            'series_id' => [
                'name'      => 'Series ID',
                'type'      => 'number',
                'required'  => true,
                'exampleValue'  => '188066'
            ]
        ],
        'By list' => [
            'list_id' => [
                'name'      => 'List ID and Type',
                'type'      => 'text',
                'required'  => true,
                'exampleValue'  => '4395&list=read'
            ]
        ]
    ];
    const LIMIT_COLS = 5;
    const LIMIT_ITEMS = 10;
    const RELEASES_URL = 'https://www.mangaupdates.com/releases.html';

    private $feedName = '';

    public function collectData()
    {
        if ($this -> queriedContext == 'By series') {
            $this -> collectDataBySeries();
        } else { //queriedContext == 'By list'
            $this -> collectDataByList();
        }
    }

    public function getURI()
    {
        if ($this -> queriedContext == 'By series') {
            $series_id = $this->getInput('series_id');
            if (!empty($series_id)) {
                return self::URI . 'releases.html?search=' . $series_id . '&stype=series';
            }
        } else {  //queriedContext == 'By list'
            return self::RELEASES_URL;
        }

        return self::URI;
    }

    public function getName()
    {
        if (!empty($this->feedName)) {
            return $this->feedName . ' - ' . self::NAME;
        }
        return parent::getName();
    }

    private function getSanitizedHash($string)
    {
        return hash('sha1', preg_replace('/[^a-zA-Z0-9\-\.]/', '', ucwords(strtolower($string))));
    }

    private function filterText($text)
    {
        return rtrim($text, '* ');
    }

    private function filterHTML($text)
    {
        return $this->filterText(html_entity_decode($text));
    }

    private function findID($manga)
    {
        // sometimes new series are on the release list that have no ID. just drop them.
        if (@$this -> filterHTML($manga -> find('a', 0) -> href) != null) {
            preg_match('/id=([0-9]*)/', $this -> filterHTML($manga -> find('a', 0) -> href), $match);
            return $match[1];
        } else {
            return 0;
        }
    }

    private function collectDataBySeries()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        // content is an unstructured pile of divs, ugly to parse
        $cols = $html->find('div#main_content div.row > div.text');
        if (!$cols) {
            returnServerError('No releases');
        }

        $rows = array_slice(
            array_chunk($cols, self::LIMIT_COLS),
            0,
            self::LIMIT_ITEMS
        );

        if (isset($rows[0][1])) {
            $this->feedName = $this->filterHTML($rows[0][1]->plaintext);
        }

        foreach ($rows as $cols) {
            if (count($cols) < self::LIMIT_COLS) {
                continue;
            }

            $item = [];
            $title = [];

            $item['content'] = '';

            $objDate = $cols[0];
            if ($objDate) {
                $item['timestamp'] = strtotime($objDate->plaintext);
            }

            $objTitle = $cols[1];
            if ($objTitle) {
                $title[] = $this->filterHTML($objTitle->plaintext);
                $item['content'] .= '<p>Series: ' . $this->filterText($objTitle->innertext) . '</p>';
            }

            $objVolume = $cols[2];
            if ($objVolume && !empty($objVolume->plaintext)) {
                $title[] = 'Vol.' . $objVolume->plaintext;
            }

            $objChapter = $cols[3];
            if ($objChapter && !empty($objChapter->plaintext)) {
                $title[] = 'Chp.' . $objChapter->plaintext;
            }

            $objAuthor = $cols[4];
            if ($objAuthor && !empty($objAuthor->plaintext)) {
                $item['author'] = $this->filterHTML($objAuthor->plaintext);
                $item['content'] .= '<p>Groups: ' . $this->filterText($objAuthor->innertext) . '</p>';
            }

            $item['title'] = implode(' ', $title);
            $item['uri'] = $this->getURI();
            $item['uid'] = $this->getSanitizedHash($item['title'] . $item['author']);

            $this->items[] = $item;
        }
    }

    private function collectDataByList()
    {
        $this -> feedName = 'Releases';
        $list = [];

        $releasesHTML = getSimpleHTMLDOM(self::RELEASES_URL);

        $list_id = $this -> getInput('list_id');
        $listHTML = getSimpleHTMLDOM('https://www.mangaupdates.com/mylist.html?id=' . $list_id);

        //get ids of the manga that the user follows,
        $parts = $listHTML -> find('table#ptable tr > td.pl');
        foreach ($parts as $part) {
            $list[] = $this -> findID($part);
        }

        //similar to above, but the divs are in groups of 3.
        $cols = $releasesHTML -> find('div#main_content div.row > div.pbreak');
        $rows = array_slice(array_chunk($cols, 3), 0);

        foreach ($rows as $cols) {
            //check if current manga is in user's list.
            $id = $this -> findId($cols[0]);
            if (!array_search($id, $list)) {
                continue;
            }

            $item = [];
            $title = [];

            $item['content'] = '';

            $objTitle = $cols[0];
            if ($objTitle) {
                $title[] = $this->filterHTML($objTitle->plaintext);
                $item['content'] .= '<p>Series: ' . $this->filterHTML($objTitle -> innertext) . '</p>';
            }

            $objVolChap = $cols[1];
            if ($objVolChap && !empty($objVolChap->plaintext)) {
                $title[] = $this -> filterHTML($objVolChap -> innertext);
            }

            $objAuthor = $cols[2];
            if ($objAuthor && !empty($objAuthor->plaintext)) {
                $item['author'] = $this->filterHTML($objAuthor -> plaintext);
                $item['content'] .= '<p>Groups: ' . $this->filterHTML($objAuthor -> innertext) . '</p>';
            }

            $item['title'] = implode(' ', $title);
            $item['uri'] = self::URI . 'releases.html?search=' . $id . '&stype=series';
            $item['uid'] = $this->getSanitizedHash($item['title'] . $item['author']);

            $this->items[] = $item;
        }
    }
}
