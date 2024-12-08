<?php

class LogicMastersBridge extends XPathAbstract
{
    const NAME = 'Logic Masters Deutschland e.V.';
    const URI = 'https://logic-masters.de/';
    const DESCRIPTION = 'Aktuelles';
    const MAINTAINER = 'hleskien';

    const FEED_SOURCE_URL = 'https://logic-masters.de/';
    //const XPATH_EXPRESSION_FEED_ICON = './/link[@rel="SHORTCUT ICON"]/@href';
    const XPATH_EXPRESSION_ITEM = '//div[@class="aktuelles_eintrag"]';
    const XPATH_EXPRESSION_ITEM_TITLE = './div[@class="aktuelles_titel"]';
    const XPATH_EXPRESSION_ITEM_CONTENT = './p';
    //const XPATH_EXPRESSION_ITEM_URI = './a/@href';
    //const XPATH_EXPRESSION_ITEM_AUTHOR = './/';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './div[@class="aktuelles_datum"]';
    //const XPATH_EXPRESSION_ITEM_ENCLOSURES = './';
    //const XPATH_EXPRESSION_ITEM_CATEGORIES = './/';

    protected function formatItemTimestamp($value)
    {
        $formatter = new IntlDateFormatter('de', IntlDateFormatter::LONG, IntlDateFormatter::NONE);
        return $formatter->parse($value);
    }
}