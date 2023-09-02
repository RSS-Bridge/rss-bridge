<?php

class NiusBridge extends XPathAbstract
{
    const NAME = 'Nius';
    const URI = 'https://www.nius.de/news';
    const DESCRIPTION = 'Die Stimme der Mehrheit';
    const MAINTAINER = 'Niehztog';

    const CACHE_TIMEOUT = 3600;

    const FEED_SOURCE_URL = 'https://www.nius.de/news';
    const XPATH_EXPRESSION_ITEM = './/div[contains(@class, "compact-story") or contains(@class, "regular-story")]';
    const XPATH_EXPRESSION_ITEM_TITLE = './/h2[@class="title"]//node()';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/h2[@class="title"]//node()';
    const XPATH_EXPRESSION_ITEM_URI = './/a[1]/@href';

    const XPATH_EXPRESSION_ITEM_AUTHOR = 'normalize-space(.//span[@class="author"]/text()[3])';

    const XPATH_EXPRESSION_ITEM_TIMESTAMP = 'normalize-space(.//span[@class="author"]/text()[1])';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/img[@sizes]/@src';
    const XPATH_EXPRESSION_ITEM_CATEGORIES = './/div[@class="subtitle"]/text()';
    const SETTING_FIX_ENCODING = false;

    protected function formatItemTimestamp($value)
    {
        return DateTimeImmutable::createFromFormat(
            false !== strpos($value, ' Uhr') ? 'H:i \U\h\r' : 'd.m.y',
            $value,
            new DateTimeZone('Europe/Berlin')
        )->format('U');
    }

    protected function cleanMediaUrl($mediaUrl)
    {
        $result = preg_match('~https:\/\/www\.nius\.de\/_next\/image\?url=(.*)\?~', $mediaUrl, $matches);
        return $result ? $matches[1] : $mediaUrl;
    }

    protected function generateItemId(FeedItem $item)
    {
        return substr($item->getURI(), strrpos($item->getURI(), '/') + 1);
    }
}
