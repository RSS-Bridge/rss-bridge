<?php

class LuftfahrtBundesAmtBridge extends XPathAbstract
{
    const NAME = 'Luftfahrt-Bundesamt';
    const URI = 'https://www.lba.de/DE/Home/Nachrichten/nachrichten_node.html';
    const DESCRIPTION = 'alle Nachrichten: Liste aller Meldungen';
    const MAINTAINER = 'hleskien';

    const FEED_SOURCE_URL = 'https://www.lba.de/DE/Home/Nachrichten/nachrichten_node.html';
    const XPATH_EXPRESSION_FEED_ICON = './/link[@rel="shortcut icon"]/@href';
    const XPATH_EXPRESSION_ITEM = '//table/tbody/tr';
    const XPATH_EXPRESSION_ITEM_TITLE = './td[2]/a/text()';
    const XPATH_EXPRESSION_ITEM_CONTENT = './td[2]/a/text()';
    const XPATH_EXPRESSION_ITEM_URI = './td[2]/a/@href';
    //const XPATH_EXPRESSION_ITEM_AUTHOR = './/';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './td[1]';
    //const XPATH_EXPRESSION_ITEM_ENCLOSURES = './';
    //const XPATH_EXPRESSION_ITEM_CATEGORIES = './/';

    protected function provideFeedIcon(\DOMXPath $xpath)
    {
        return parent::provideFeedIcon($xpath) . '?__blob=normal&v=3';
    }

    protected function formatItemTimestamp($value)
    {
        $value = trim($value);
        if (strpos($value, 'Uhr') !== false) {
            $value = str_replace(' Uhr', '', $value);
            $dti = DateTimeImmutable::createFromFormat('d.m.Y G:i', $value);
        } else {
            $dti = DateTimeImmutable::createFromFormat('d.m.Y', $value);
            $dti = $dti->setTime(0, 0);
        }
        return $dti->getTimestamp();
    }

    // remove jsession part
    protected function formatItemUri($value)
    {
        $parts = explode(';', $value);
        return $parts[0];
    }
}

