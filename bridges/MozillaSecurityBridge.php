<?php

class MozillaSecurityBridge extends BridgeAbstract
{
    const MAINTAINER = 'm0le.net';
    const NAME = 'Mozilla Security Advisories';
    const URI = 'https://www.mozilla.org/en-US/security/advisories/';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'Mozilla Security Advisories';
    const WEBROOT = 'https://www.mozilla.org';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        $html = defaultLinkTo($html, self::WEBROOT);

        $item = [];
        $articles = $html->find('div[id="main-content"] h2');

        foreach ($articles as $element) {
            //Limit total amount of requests
            if (count($this->items) >= 20) {
                break;
            }
            $item['title'] = $element->innertext;
            $item['timestamp'] = strtotime($element->innertext);
            $item['content'] = $element->next_sibling()->innertext;
            $item['uri'] = self::URI . '?' . $item['timestamp'];
            $item['uid'] = self::URI . '?' . $item['timestamp'];
            $this->items[] = $item;
        }
    }
}
