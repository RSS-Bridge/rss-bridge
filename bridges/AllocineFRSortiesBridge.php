<?php

class AllocineFRSortiesBridge extends BridgeAbstract
{
    const MAINTAINER = 'Simounet';
    const NAME = 'AlloCiné Sorties Bridge';
    const CACHE_TIMEOUT = 25200; // 7h
    const BASE_URI = 'https://www.allocine.fr';
    const URI = self::BASE_URI . '/film/sorties-semaine/';
    const DESCRIPTION = 'Bridge for AlloCiné - Sorties cinéma cette semaine';

    public function getName()
    {
        return self::NAME;
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        foreach ($html->find('section.section.section-wrap', 0)->find('li.mdl') as $element) {
            $item = [];

            $thumb = $element->find('figure.thumbnail', 0);
            $meta = $element->find('div.meta-body', 0);
            $synopsis = $element->find('div.synopsis', 0);
            $date = $element->find('span.date', 0);

            $title = $element->find('a[class*=meta-title-link]', 0);
            $content = trim(defaultLinkTo($thumb->outertext . $meta->outertext . $synopsis->outertext, static::URI));

            // Replace image 'src' with the one in 'data-src'
            $content = preg_replace('@src="data:image/gif;base64,[A-Za-z0-9=+\/]*"@', '', $content);
            $content = preg_replace('@data-src=@', 'src=', $content);

            $item['content'] = $content;
            $item['title'] = trim($title->innertext);
            $item['timestamp'] = $this->frenchPubDateToTimestamp($date->plaintext);
            $item['uri'] = static::BASE_URI . '/' . substr($title->href, 1);
            $this->items[] = $item;
        }
    }

    private function frenchPubDateToTimestamp($date)
    {
        return strtotime(
            strtr(
                strtolower($date),
                [
                    'janvier' => 'jan',
                    'février' => 'feb',
                    'mars' => 'march',
                    'avril' => 'apr',
                    'mai' => 'may',
                    'juin' => 'jun',
                    'juillet' => 'jul',
                    'août' => 'aug',
                    'septembre' => 'sep',
                    'octobre' => 'oct',
                    'novembre' => 'nov',
                    'décembre' => 'dec'
                ]
            )
        );
    }
}
