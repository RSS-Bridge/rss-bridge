<?php

class OpenwrtSecurityBridge extends BridgeAbstract
{
    const NAME = 'OpenWrt Security Advisories';
    const URI = 'https://openwrt.org/advisory/start';
    const DESCRIPTION = 'Security Advisories published by openwrt.org';
    const MAINTAINER = 'mschwld';
    const CACHE_TIMEOUT = 3600;
    const WEBROOT = 'https://openwrt.org';

    public function collectData()
    {
        $item = [];
        $html = getSimpleHTMLDOM(self::URI);

        $advisories = $html->find('div[class=plugin_nspages]', 0);

        foreach ($advisories->find('a[class=wikilink1]') as $element) {
            $item = [];

            $row = $element->innertext;

            $item['title'] = substr($row, 0, strpos($row, ' - '));
            $item['timestamp'] = $this->getDate($element->href);
            $item['uri'] = self::WEBROOT . $element->href;
            $item['uid'] = self::WEBROOT . $element->href;
            $item['content'] = substr($row, strpos($row, ' - ') + 3);
            $item['author'] = 'OpenWrt Project';

            $this->items[] = $item;
        }
    }

    private function getDate($href)
    {
        $date = substr($href, -12);
        return $date;
    }
}
