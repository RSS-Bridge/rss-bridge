<?php

class NovelUpdatesBridge extends BridgeAbstract
{
    const MAINTAINER = 'albirew';
    const NAME = 'Novel Updates';
    const URI = 'https://www.novelupdates.com';
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

        $this->seriesTitle = $fullhtml->find('div.seriestitlenu', 0)->plaintext;
        $html = $fullhtml->find('table#myTable tbody', 0);
        foreach ($html->find('tr') as $element) {
            $item = [];
            $item['title'] = $element->find('td', 2)->find('span', 0)->plaintext;
            $item['author'] = $element->find('a', 0)->plaintext;
            $item['timestamp'] = strtotime($element->find('td', 0)->plaintext);
            $item['content'] = $this->seriesTitle
                . ' - '
                . $item['title']
                . ' - by '
                . $item['author']
                . '<br>'
                . $fullhtml->find('div.seriesimg', 0)->innertext;

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
