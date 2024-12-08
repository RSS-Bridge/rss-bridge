<?php

class FliegermagazinBridge extends XPathAbstract
{
    const NAME = 'fliegermagazin';
    const URI = 'https://www.fliegermagazin.de/news-fuer-piloten/';
    const DESCRIPTION = 'News für Piloten';
    const MAINTAINER = 'hleskien';

    const FEED_SOURCE_URL = 'https://www.fliegermagazin.de/news-fuer-piloten/';
    const XPATH_EXPRESSION_FEED_ICON = './/link[@rel="shortcut icon"]/@href';
    const XPATH_EXPRESSION_ITEM = '//article[@data-type="post"]';
    const XPATH_EXPRESSION_ITEM_TITLE = './/h3/a/text()';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/h3/a/text()';
    const XPATH_EXPRESSION_ITEM_URI = './/h3/a/@href';
    const XPATH_EXPRESSION_ITEM_AUTHOR = './/p[@class="author-field"]';
    // Timestamp kann nur durch Laden des Artikels herausgefunden werden
    //const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/span/i';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/img/@src';
    //const XPATH_EXPRESSION_ITEM_CATEGORIES = './/';
}

