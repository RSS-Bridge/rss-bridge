<?php

class FourchanBridge extends BridgeAbstract
{
    const MAINTAINER = 'mitsukarenai';
    const NAME = '4chan';
    const URI = 'https://boards.4chan.org/';
    const CACHE_TIMEOUT = 300; // 5min
    const DESCRIPTION = 'Returns posts from the specified thread';

    const PARAMETERS = [ [
        'c' => [
            'name' => 'Thread category',
            'required' => true,
            'exampleValue' => 'po',
        ],
        't' => [
            'name' => 'Thread number',
            'type' => 'number',
            'exampleValue' => '597271',
            'required' => true
        ]
    ]];

    public function getURI()
    {
        if (!is_null($this->getInput('c')) && !is_null($this->getInput('t'))) {
            return static::URI . $this->getInput('c') . '/thread/' . $this->getInput('t');
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('div.postContainer') as $element) {
            $item = [];
            $item['id'] = $element->find('.post', 0)->getAttribute('id');
            $item['uri'] = $this->getURI() . '#' . $item['id'];
            $item['timestamp'] = $element->find('span.dateTime', 0)->getAttribute('data-utc');
            $item['author'] = $element->find('span.name', 0)->plaintext;

            $file = $element->find('.file', 0);

            if (!empty($file)) {
                $item['image'] = $element->find('.file a', 0)->href;
                $item['imageThumb'] = $element->find('.file img', 0)->src;
                if (!isset($item['imageThumb']) and strpos($item['image'], '.swf') !== false) {
                    $item['imageThumb'] = 'http://i.imgur.com/eO0cxf9.jpg';
                }
            }

            if (!empty($element->find('span.subject', 0)->innertext)) {
                $item['subject'] = $element->find('span.subject', 0)->innertext;
            }

            $item['title'] = 'reply ' . $item['id'] . ' | ' . $item['author'];
            if (isset($item['subject'])) {
                $item['title'] = $item['subject'] . ' - ' . $item['title'];
            }

            $content = $element->find('.postMessage', 0)->innertext;
            $content = str_replace('href="#p', 'href="' . $this->getURI() . '#p', $content);
            $item['content'] = '<span id="' . $item['id'] . '">' . $content . '</span>';

            if (isset($item['image'])) {
                $item['content'] = '<a href="'
                . $item['image']
                . '"><img alt="'
                . $item['id']
                . '" src="'
                . $item['imageThumb']
                . '" /></a><br>'
                . $item['content'];
            }
            $this->items[] = $item;
        }
        $this->items = array_reverse($this->items);
    }
}
