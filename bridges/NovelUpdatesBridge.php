<?php

class NovelUpdatesBridge extends BridgeAbstract
{
    const MAINTAINER = 'albirew';
    const NAME = 'Novel Updates';
    const URI = 'https://www.novelupdates.com/';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'Returns releases from Novel Updates';
    const PARAMETERS = [ [
        'n' => [
            'name' => 'Novel name as found in the url',
            'exampleValue' => 'spirit-realm',
            'required' => true
        ]
    ]];

    private $seriesTitle = '';

    public function getURI()
    {
        if (!is_null($this->getInput('n'))) {
            return static::URI . '/series/' . $this->getInput('n') . '/';
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $fullhtml = getSimpleHTMLDOM($this->getURI());

        $this->seriesTitle = $fullhtml->find('h4.seriestitle', 0)->plaintext;
        // dirty fix for nasty simpledom bug: https://github.com/sebsauvage/rss-bridge/issues/259
        // forcefully removes tbody
        $html = $fullhtml->find('table#myTable', 0)->innertext;
        $html = stristr($html, '<tbody>'); //strip thead
        $html = stristr($html, '<tr>'); //remove tbody
        $html = str_get_html(stristr($html, '</tbody>', true)); //remove last tbody and get back as an array
        foreach ($html->find('tr') as $element) {
            $item = [];
            $item['uri'] = $element->find('td', 2)->find('a', 0)->href;
            $item['title'] = $element->find('td', 2)->find('a', 0)->plaintext;
            $item['team'] = $element->find('td', 1)->innertext;
            $item['timestamp'] = strtotime($element->find('td', 0)->plaintext);
            $item['content'] = '<a href="'
                . $item['uri']
                . '">'
                . $this->seriesTitle
                . ' - '
                . $item['title']
                . '</a> by '
                . $item['team']
                . '<br><a href="'
                . $item['uri']
                . '">'
                . $fullhtml->find('div.seriesimg', 0)->innertext
                . '</a>';

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if (!empty($this->seriesTitle)) {
            return $this->seriesTitle . ' - ' . static::NAME;
        }

        return parent::getName();
    }
}
