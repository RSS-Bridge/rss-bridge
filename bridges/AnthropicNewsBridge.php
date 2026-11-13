<?php

class AnthropicNewsBridge extends XPathAbstract
{
    const NAME = 'Anthropic News';
    const URI = 'https://www.anthropic.com/news';
    const DESCRIPTION = 'Announcements, product updates, and policy news from Anthropic';
    const MAINTAINER = 'Niehztog';
    const CACHE_TIMEOUT = 3600;

    const FEED_SOURCE_URL = 'https://www.anthropic.com/news';
    const XPATH_EXPRESSION_ITEM = '//li[@data-listing-row] | //*[header/time][not(.//a[.//h2 or .//h3]/@href = //li[@data-listing-row]//a/@href)]';
    const XPATH_EXPRESSION_ITEM_TITLE = './/*[self::h2 or self::h3]';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/p[@data-component="Body"] | .//*[contains(@class, "description")]//p';
    const XPATH_EXPRESSION_ITEM_URI = './/a[.//h2 or .//h3]/@href';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/time';
    const XPATH_EXPRESSION_ITEM_CATEGORIES = './header//a | .//a[contains(@class, "category")]';
}
