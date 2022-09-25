<?php

class BlaguesDeMerdeBridge extends BridgeAbstract
{
    const MAINTAINER = 'superbaillot.net, logmanoriginal';
    const NAME = 'Blagues De Merde';
    const URI = 'http://www.blaguesdemerde.fr/';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'Blagues De Merde';

    public function getIcon()
    {
        return self::URI . 'assets/img/favicon.ico';
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        foreach ($html->find('div.blague') as $element) {
            $item = [];

            $item['uri'] = static::URI . '#' . $element->id;
            $item['author'] = $element->find('div[class="blague-footer"] p strong', 0)->plaintext;

            // Let the title be everything up to the first <br>
            $item['title'] = trim(explode("\n", $element->find('div.text', 0)->plaintext)[0]);

            $item['content'] = strip_tags($element->find('div.text', 0));

            // timestamp is part of:
            // <p>Par <strong>{author}</strong> le {date} dans <strong>{category}</strong></p>
            preg_match(
                '/.+le(.+)dans.*/',
                $element->find('div[class="blague-footer"]', 0)->plaintext,
                $matches
            );

            $item['timestamp'] = strtotime($matches[1]);

            $this->items[] = $item;
        }
    }
}
