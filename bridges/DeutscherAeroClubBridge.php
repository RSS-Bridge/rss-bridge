<?php

class DeutscherAeroClubBridge extends XPathAbstract
{
    const NAME = 'Deutscher Aero Club';
    const URI = 'https://www.daec.de/news/';
    const DESCRIPTION = 'News aus Luftsport und Dachverband';
    const MAINTAINER = 'hleskien';

    const FEED_SOURCE_URL = 'https://www.daec.de/news/';
    const XPATH_EXPRESSION_FEED_ICON = './/link[@rel="icon"][1]/@href';
    const XPATH_EXPRESSION_ITEM = '//div[contains(@class, "news-list-view")]/div[contains(@class, "article")]';
    const XPATH_EXPRESSION_ITEM_TITLE = './/span[@itemprop="headline"]';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/div[@itemprop="description"]/p';
    const XPATH_EXPRESSION_ITEM_URI = './/div[@class="news-header"]//a/@href';
    //const XPATH_EXPRESSION_ITEM_AUTHOR = './/';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/time/@datetime';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/img/@src';
    //const XPATH_EXPRESSION_ITEM_CATEGORIES = './/';

    protected function formatItemTimestamp($value)
    {
        $dti = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        $dti = $dti->setTime(0, 0, 0);
        return $dti->getTimestamp();
    }
}

