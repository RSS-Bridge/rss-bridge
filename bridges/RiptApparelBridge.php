<?php

class RiptApparelBridge extends BridgeAbstract
{
    const NAME = 'RIPT Apparel';
    const URI = 'https://www.riptapparel.com';
    const DESCRIPTION = 'Returns the daily designs';
    const MAINTAINER = 'Bockiii';
    const PARAMETERS = [];

    const CACHE_TIMEOUT = 60 * 60 * 3; // 3 hours

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        foreach ($html->find('div.daily-designs', 0)->find('div.collection') as $element) {
            $title = $element->find('div.design-info', 0)->find('div.title', 0)->innertext;
            $uri = self::URI . $element->find('div.design-info', 0)->find('a', 0)->href;
            $today = date('m/d/Y');
            $imagesrcset = $element->find('div.design-images', 0)->find('div[data-subtype="Mens"]', 0)->find('img', 0);
            $image = rtrim(explode(',', $imagesrcset->getAttribute('data-srcset'))[2], ' 900w');
            $item = [];
            $item['uri'] = $uri;
            $item['title'] = $title;
            $item['uid'] = $title;
            $item['timestamp'] = $today;
            $item['content'] = '<a href="'
            . $uri
            . '"><img src="'
            . $image
            . '" /></a>';

            $this->items[] = $item;
        }
    }
}
