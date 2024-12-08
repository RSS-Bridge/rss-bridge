<?php

/**
 * See https://reporterre.net/spip.php?page=backend-simple
 */
class ReporterreBridge extends BridgeAbstract
{
    const MAINTAINER = 'nyutag';
    const NAME = 'Reporterre Bridge';
    const URI = 'https://www.reporterre.net/';
    const DESCRIPTION = 'Returns the newest articles. See also their official feed https://reporterre.net/spip.php?page=backend-simple';

    public function collectData()
    {
        //$url = self::URI . 'spip.php?page=backend';
        $url = self::URI . 'spip.php?page=backend-simple';
        $html = getSimpleHTMLDOM($url);
        $limit = 0;

        foreach ($html->find('item') as $element) {
            if ($limit < 5) {
                $item = [];
                $item['title'] = html_entity_decode($element->find('title', 0)->plaintext);
                $item['timestamp'] = strtotime($element->find('dc:date', 0)->plaintext);
                $item['uri'] = $element->find('guid', 0)->innertext;
                //$item['content'] = html_entity_decode($this->extractContent($item['uri']));
                $item['content'] = htmlspecialchars_decode($element->find('description', 0)->plaintext);
                $this->items[] = $item;
                $limit++;
            }
        }
    }

    private function extractContent($url)
    {
        $html2 = getSimpleHTMLDOM($url);
        $html2 = defaultLinkTo($html2, self::URI);

        foreach ($html2->find('div[style=text-align:justify]') as $e) {
            $text = $e->outertext;
        }

        $html2->clear();
        unset($html2);

        $text = strip_tags($text, '<p><br><a><img>');
        return $text;
    }
}
