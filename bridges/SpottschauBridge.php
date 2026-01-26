<?php

class SpottschauBridge extends BridgeAbstract
{
    const NAME = 'Härringers Spottschau Bridge';
    const URI = 'https://spottschau.com/';
    const DESCRIPTION = 'Returns the latest strip from the "Härringers Spottschau" comic.';
    const MAINTAINER = 'sal0max';

    const CACHE_TIMEOUT = 86400; // 24h

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached(self::URI, self::CACHE_TIMEOUT)
            or returnServerError('Could not retrieve ' . self::URI);

        $strip = $html->find('div.strip > a', 0)
            or returnServerError('Could not find the proper HTML element of the strip.');

        defaultLinkTo($strip, self::URI);
        // Get URL from image src attribute
        $src = $strip->children(0)->src;

        $this->items[] = array(
            'uri' => self::URI,
            'title' => 'Strip der Woche',
            'content' => '<img src="' . $src . '">',
            'enclosures' => array($src),
            'author' => 'Christoph Härringer',
            'uid' => $src,
            );
    }
}
