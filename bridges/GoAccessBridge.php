<?php

class GoAccessBridge extends BridgeAbstract
{
    const MAINTAINER = 'Simounet';
    const NAME = 'GoAccess';
    const URI_BASE = 'https://goaccess.io';
    const URI = self::URI_BASE . '/release-notes';
    const CACHE_TIMEOUT = 21600; //6h
    const DESCRIPTION = 'GoAccess releases.';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        $container = $html->find('.container.content', 0);
        foreach ($container->find('div') as $element) {
            $titleEl = $element->find('h2', 0);
            $dateEl = $titleEl->find('small', 0);
            $date = trim($dateEl->plaintext);
            $title = is_object($titleEl) ? str_replace($date, '', $titleEl->plaintext) : '';
            $linkEl = $titleEl->find('a', 0);
            $link = is_object($linkEl) ? $linkEl->href : '';
            $postUrl = self::URI . $link;

            $contentEl = $element->find('.dl-horizontal', 0);
            $content = '<dl>' . $contentEl->xmltext() . '</dl>';

            $item = [];
            $item['uri'] = $postUrl;
            $item['timestamp'] = strtotime($date);
            $item['title'] = $title;
            $item['content'] = $content;

            $this->items[] = $item;
        }
    }
}
