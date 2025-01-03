<?php

class OMonlineBridge extends BridgeAbstract
{
    const NAME        = 'OM Online Bridge';
    const URI         = 'https://www.om-online.de';
    const DESCRIPTION = 'RSS feed for OM Online';
    const MAINTAINER  = 'jummo4@yahoo.de';
    const PARAMETERS = [
                           [
                               'ort' => [
                               'name' => 'Ortsname',
                               'title' => 'Für die Anzeige von Beitragen nur aus einem Ort oder mehreren Orten 
                               geben einen Orstnamen ein. Mehrere Ortsnamen müssen mit / getrennt eingeben werden, 
                               z.B. Vechta/Cloppenburg. Groß- und Kleinschreibung beachten!'
                               ]
                           ]
                       ];

    public function collectData()
    {
        if (!empty($this->getInput('ort'))) {
            $url = sprintf('%s/ort/%s', self::URI, $this->getInput('ort'));
        } else {
            $url = sprintf('%s', self::URI);
        }

        $html = getSimpleHTMLDOM($url);

        $html = defaultLinkTo($html, $url);

        foreach ($html->find('div.molecule-teaser > a ') as $index => $a) {
            $item = [];

            $articlePath = $a->href;

            $articlePageHtml = getSimpleHTMLDOMCached($articlePath, self::CACHE_TIMEOUT);

            $articlePageHtml = defaultLinkTo($articlePageHtml, self::URI);

            $contents = $articlePageHtml->find('div.molecule-article', 0);

            $item['uri'] = $articlePath;
            $item['title'] = $contents->find('h1', 0)->innertext;

            $contents->find('div.col-12 col-md-10 offset-0 offset-md-1', 0);

            $item['content'] = $contents->innertext;
            $item['timestamp'] = $this->extractDate2($a->plaintext);
            $this->items[] = $item;

            if (count($this->items) >= 10) {
                break;
            }
        }
    }

    private function extractDate2($text)
    {
        $dateRegex = '/^([0-9]{4}\/[0-9]{1,2}\/[0-9]{1,2})/';

        $text = trim($text);

        if (preg_match($dateRegex, $text, $matches)) {
            return $matches[1];
        }

        return '';
    }
}
