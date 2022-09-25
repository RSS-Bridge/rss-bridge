<?php

class FabriceBellardBridge extends BridgeAbstract
{
    const NAME = 'Fabrice Bellard';
    const URI = 'https://bellard.org/';
    const DESCRIPTION = "Fabrice Bellard's Home Page";
    const MAINTAINER = 'somini';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        foreach ($html->find('p') as $obj) {
            $item = [];

            $html = defaultLinkTo($html, $this->getURI());

            $links = $obj->find('a');
            if (count($links) > 0) {
                $link_uri = $links[0]->href;
            } else {
                $link_uri = $this->getURI();
            }

            /* try to make sure the link is valid */
            if ($link_uri[-1] !== '/' && strpos($link_uri, '/') === false) {
                $link_uri = $link_uri . '/';
            }

            $item['title'] = strip_tags($obj->innertext);
            $item['uri'] = $link_uri;
            $item['content'] = $obj->innertext;

            $this->items[] = $item;
        }
    }
}
