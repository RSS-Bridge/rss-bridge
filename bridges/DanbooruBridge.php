<?php

class DanbooruBridge extends BridgeAbstract
{
    const MAINTAINER = 'mitsukarenai, logmanoriginal';
    const NAME = 'Danbooru';
    const URI = 'http://donmai.us/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns images from given page';

    const PARAMETERS = [
        'global' => [
            'p' => [
                'name' => 'page',
                'defaultValue' => 1,
                'type' => 'number'
            ],
            't' => [
                'type' => 'text',
                'name' => 'tags',
                'exampleValue' => 'cosplay',
            ]
        ],
        0 => []
    ];

    const PATHTODATA = 'article';
    const IDATTRIBUTE = 'data-id';
    const TAGATTRIBUTE = 'alt';

    protected function getFullURI()
    {
        return $this->getURI()
        . 'posts?&page=' . $this->getInput('p')
        . '&tags=' . urlencode($this->getInput('t'));
    }

    protected function getTags($element)
    {
        return $element->find('img', 0)->getAttribute(static::TAGATTRIBUTE);
    }

    protected function getItemFromElement($element)
    {
        // Fix links
        defaultLinkTo($element, $this->getURI());

        $item = [];
        $item['uri'] = html_entity_decode($element->find('a', 0)->href);
        $item['postid'] = (int)preg_replace('/[^0-9]/', '', $element->getAttribute(static::IDATTRIBUTE));
        $item['timestamp'] = time();
        $thumbnailUri = $element->find('img', 0)->src;
        $item['categories'] = array_filter(explode(' ', $this->getTags($element)));
        $item['title'] = $this->getName() . ' | ' . $item['postid'];
        $item['content'] = '<a href="'
        . $item['uri']
        . '"><img src="'
        . $thumbnailUri
        . '" /></a><br>Tags: '
        . $this->getTags($element);

        return $item;
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getFullURI());

        foreach ($html->find(static::PATHTODATA) as $element) {
            $this->items[] = $this->getItemFromElement($element);
        }
    }
}
