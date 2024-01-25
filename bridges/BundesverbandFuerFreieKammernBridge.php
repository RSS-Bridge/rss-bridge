<?php

class BundesverbandFuerFreieKammernBridge extends XPathAbstract
{
    const NAME = 'Bundesverband für freie Kammern e.V.';
    const URI = 'https://www.bffk.de/aktuelles/aktuelle-nachrichten.html';
    const DESCRIPTION = 'Aktuelle Nachrichten';
    const MAINTAINER = 'hleskien';

    const FEED_SOURCE_URL = 'https://www.bffk.de/aktuelles/aktuelle-nachrichten.html';
    //const XPATH_EXPRESSION_FEED_ICON = './/link[@rel="icon"]/@href';
    const XPATH_EXPRESSION_ITEM = '//ul[@class="article-list"]/li';
    const XPATH_EXPRESSION_ITEM_TITLE = './/a/text()';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/a/text()';
    const XPATH_EXPRESSION_ITEM_URI = './/a/@href';
    //const XPATH_EXPRESSION_ITEM_AUTHOR = './/';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/span/i';
    //const XPATH_EXPRESSION_ITEM_ENCLOSURES = './';
    //const XPATH_EXPRESSION_ITEM_CATEGORIES = './/';

    protected function formatItemTimestamp($value)
    {
        $value = trim($value, '()');
        $dti = DateTimeImmutable::createFromFormat('d.m.Y', $value);
        $dti = $dti->setTime(0, 0, 0);
        return $dti->getTimestamp();
    }
}
