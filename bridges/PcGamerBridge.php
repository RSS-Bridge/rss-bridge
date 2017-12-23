<?php
class PcGamerBridge extends BridgeAbstract
{
    const NAME = 'PC Gamer';
    const URI = 'https://www.pcgamer.com/';
    const DESCRIPTION = 'PC Gamer Most Read Stories';
    const MAINTAINER = 'mdemoss';

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getURI(), 300);
        $stories = $html->find('div#popularcontent li.most-popular-item');
        foreach ($stories as $element) {
            $item['uri'] = $element->find('a', 0)->href;
            $articleHtml = getSimpleHTMLDOMCached($item['uri']);
            $item['title'] = $element->find('h4 a', 0)->plaintext;
            $item['timestamp'] = strtotime($articleHtml->find('meta[name=pub_date]', 0)->content);
            $item['content'] = $articleHtml->find('meta[name=description]', 0)->content;
            $item['author'] = $articleHtml->find('a[itemprop=author]', 0)->plaintext;
            $this->items[] = $item;
        }
    }
}
