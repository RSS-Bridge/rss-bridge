<?php

class ASRockNewsBridge extends BridgeAbstract
{
    const NAME = 'ASRock News Bridge';
    const URI = 'https://www.asrock.com';
    const DESCRIPTION = 'Returns latest news articles';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [];

    const CACHE_TIMEOUT = 3600; // 1 hour

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . '/news/index.asp');

        $html = defaultLinkTo($html, self::URI . '/news/');

        foreach ($html->find('div.inner > a') as $index => $a) {
            $item = [];

            $articlePath = $a->href;

            $articlePageHtml = getSimpleHTMLDOMCached($articlePath, self::CACHE_TIMEOUT);

            $articlePageHtml = defaultLinkTo($articlePageHtml, self::URI);

            $contents = $articlePageHtml->find('div.Contents', 0);

            $item['uri'] = $articlePath;
            $item['title'] = $contents->find('h3', 0)->innertext;

            $contents->find('h3', 0)->outertext = '';

            $item['content'] = $contents->innertext;
            $item['timestamp'] = $this->extractDate($a->plaintext);
            $item['enclosures'][] = $a->find('img', 0)->src;
            $this->items[] = $item;

            if (count($this->items) >= 10) {
                break;
            }
        }
    }

    private function extractDate($text)
    {
        $dateRegex = '/^([0-9]{4}\/[0-9]{1,2}\/[0-9]{1,2})/';

        $text = trim($text);

        if (preg_match($dateRegex, $text, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
