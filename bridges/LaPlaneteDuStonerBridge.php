<?php

class LaPlaneteDuStonerBridge extends BridgeAbstract
{
    const NAME = 'La Planete Du Stoner';
    const MAINTAINER = 'abon999';
    const URI = 'https://www.laplanetedustoner.net/';
    const DESCRIPTION = 'Returns last articles from French stoner blog "La Planete du Stoner"';

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(self::URI);
        foreach ($dom->find('div.blog-post.hentry.index-post') as $div) {
            $h2 = $div->find('h2', 0);
            $a = $h2->find('a', 0);
            $uri = $a->href;
            $snippet = $div->find('p.post-snippet', 0);
            $this->items[] = [
                'title' => $a->plaintext,
                'uri' => $uri,
                'content' => $snippet->plaintext,
            ];
        }
    }
}