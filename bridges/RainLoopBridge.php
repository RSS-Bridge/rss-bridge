<?php

class RainLoopBridge extends BridgeAbstract
{
    const MAINTAINER = 'Simounet';
    const NAME = 'RainLoop';
    const URI_BASE = 'https://www.rainloop.net';
    const URI = self::URI_BASE . '/changelog/';
    const CACHE_TIMEOUT = 21600; //6h
    const DESCRIPTION = 'RainLoop\'s changelog';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        $mainContent = $html->find('.main-center', 0);
        $elements = $mainContent->find('.row-fluid');
        foreach ($elements as $i => $element) {
            if ($i === 0) {
                continue;
            }

            $titleEl = $element->find('.h3', 0);
            $title = is_object($titleEl) ? $titleEl->plaintext : '';

            $postUrl = self::URI . $title;

            $contentEl = $element->find('.span9', 0);
            $content = is_object($contentEl) ? $contentEl->xmltext() : '';

            $item = [];
            $item['uri'] = $postUrl;
            $item['title'] = $title;
            $item['content'] = $content;
            $item['timestamp'] = strtotime('now');

            $this->items[] = $item;
        }
    }
}
