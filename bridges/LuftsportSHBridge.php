<?php

class LuftsportSHBridge extends XPathAbstract
{
    const NAME = 'Luftsportverband Schleswig-Holstein';
    const URI = 'https://www.luftsport-sh.de/start.html';
    const DESCRIPTION = 'Aktuelles vom Luftsportverband Schleswig-Holstein e.V.';
    const MAINTAINER = 'hleskien';

    const FEED_SOURCE_URL = 'https://www.luftsport-sh.de/start.html';
    const XPATH_EXPRESSION_FEED_ICON = './/link[@rel="icon" and @sizes="16x16"]/@href';
    const XPATH_EXPRESSION_ITEM = '//div[contains(@class, "mod_newslist")]/div';
    const XPATH_EXPRESSION_ITEM_TITLE = './/*[@itemprop="name"]/a/text()';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/div[@itemprop="description"]/p/text()';
    const XPATH_EXPRESSION_ITEM_URI = './h3/a/@href';
    //const XPATH_EXPRESSION_ITEM_AUTHOR = './/';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/time/@datetime';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/img/@src';
    //const XPATH_EXPRESSION_ITEM_CATEGORIES = './/';

    protected function formatItemTimestamp($value)
    {
        $dti = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $value);
        return $dti->getTimestamp();
    }
}
