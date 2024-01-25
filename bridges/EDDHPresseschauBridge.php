<?php

class EDDHPresseschauBridge extends XPathAbstract
{
    const NAME = 'EDDH.de Presseschau';
    const URI = 'https://eddh.de/presse/presseschau.php';
    const DESCRIPTION = 'Luftfahrt-Presseschau: Presse-Artikel aus der Luftfahrt';
    const MAINTAINER = 'hleskien';

    const FEED_SOURCE_URL = 'https://eddh.de/presse/presseschau.php';
    //const XPATH_EXPRESSION_FEED_ICON = './/link[@rel="icon"]/@href';
    const XPATH_EXPRESSION_ITEM = '//table//table[.//p[@class="pressnews"]]//td';
    const XPATH_EXPRESSION_ITEM_TITLE = './h4';
    const XPATH_EXPRESSION_ITEM_CONTENT = './p[@class="pressnews"]';
    const XPATH_EXPRESSION_ITEM_URI = './p[@class="pressnews"]/a/@href';
    const XPATH_EXPRESSION_ITEM_AUTHOR = './p[@class="quelle"]';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './p[@class="quelle"]';
    //const XPATH_EXPRESSION_ITEM_ENCLOSURES = './';
    //const XPATH_EXPRESSION_ITEM_CATEGORIES = './/';

    public function getIcon()
    {
        return 'https://eddh.de/favicon.ico';
    }

    protected function formatItemAuthor($value)
    {
        $parts = explode('(', $value);
        $author = trim($parts[0]);
        return $author;
    }

    protected function formatItemTimestamp($value)
    {
        $parts = explode('(', $value);
        $ws = ["\n", "\t", ' ', ')'];
        $value = str_replace($ws, '', $parts[1]);
        $dti = DateTimeImmutable::createFromFormat('d.m.Y', $value);
        $dti = $dti->setTime(0, 0, 0);
        return $dti->getTimestamp();
    }
}
