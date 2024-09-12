<?php

class InternetDownloadManagerBridge extends BridgeAbstract
{
    const NAME = 'Internet Download Manager News';
    const URI = 'https://www.internetdownloadmanager.com/news.html';
    const DESCRIPTION = 'IDM update changelog';
    const MAINTAINER = 'tillcash';
    const MAX_ITEMS = 10;

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(self::URI);
        $elements = $dom->find('.col-center.with__nav > h3');
        $count = 0;

        foreach ($elements as $element) {
            if ($count >= self::MAX_ITEMS) {
                break;
            }

            // Extract the release date information
            $dateInfo = $element->next_sibling()->plaintext;
            preg_match('/\(Released: (.*?)\)/', $dateInfo, $matches);
            $date = $matches[1] ?? time();

            // Extract the content of the changelog
            $content = $element->next_sibling()->next_sibling()->outertext;

            $this->items[] = [
                'title' => $element->plaintext,
                'timestamp' => $date,
                'content' => $content,
            ];

            $count++;
        }
    }
}
