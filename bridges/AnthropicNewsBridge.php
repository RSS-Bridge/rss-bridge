<?php

class AnthropicNewsBridge extends XPathAbstract
{
    const NAME = 'Anthropic News';
    const URI = 'https://www.anthropic.com/news';
    const DESCRIPTION = 'Announcements, product updates, and policy news from Anthropic';
    const MAINTAINER = 'Niehztog';
    const CACHE_TIMEOUT = 3600;

    const FEED_SOURCE_URL = 'https://www.anthropic.com/news';
    const XPATH_EXPRESSION_ITEM = '//a[contains(@class, "PublicationList") and contains(@class, "listItem")]';
    const XPATH_EXPRESSION_ITEM_TITLE = './/span[contains(@class, "title")]';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/span[contains(@class, "title")]/text()';
    const XPATH_EXPRESSION_ITEM_URI = './@href';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/time[contains(@class, "date")]';
    const XPATH_EXPRESSION_ITEM_CATEGORIES = './/span[contains(@class, "subject")]';
}
