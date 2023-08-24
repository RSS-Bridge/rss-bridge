<?php

class Kanali6Bridge extends XPathAbstract
{
    const NAME = 'Kanali6 Latest Podcasts';
    const DESCRIPTION = 'Returns the latest podcasts';
    const URI = 'https://kanali6.com.cy/mp3/TOC.html';

    const FEED_SOURCE_URL = 'https://kanali6.com.cy/mp3/TOC.xml';
    const XPATH_EXPRESSION_ITEM = '//recording[position() <= 50]';
    const XPATH_EXPRESSION_ITEM_TITLE = './title';
    const XPATH_EXPRESSION_ITEM_CONTENT = './durationvisual';
    const XPATH_EXPRESSION_ITEM_URI = './filename';
    const XPATH_EXPRESSION_ITEM_AUTHOR = './/producersname';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './recfinisheddatetime';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = './filename';

    public function getURI()
    {
        return self::URI;
    }
}
