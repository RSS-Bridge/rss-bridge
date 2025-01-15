<?php

class MondeDiploBridge extends BridgeAbstract
{
    const MAINTAINER = 'Pitchoule';
    const NAME = 'Monde Diplomatique';
    const URI = 'https://www.monde-diplomatique.fr';
    const CACHE_TIMEOUT = 21600; //6h
    const DESCRIPTION = 'Returns most recent results from MondeDiplo.';

    private function cleanText($text)
    {
        return trim(str_replace(['&nbsp;', '&nbsp'], ' ', $text));
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        foreach ($html->find('div.unarticle') as $article) {
            $element = $article->parent();
            $titleElement = $element->find('h3', 0);
            if (!$titleElement) {
                continue;
            }
            $title = $titleElement->plaintext;
            $datesAuteursElement = $element->find('div.dates_auteurs', 0);
            $datesAuteurs = is_null($datesAuteursElement) ? '' : $element->find('div.dates_auteurs', 0)->plaintext;
            $item = [];
            $item['uri'] = urljoin(self::URI, $element->href);
            $item['title'] = $this->getItemTitle($title, $datesAuteurs);
            $item['content'] = $this->cleanText(str_replace([$title, $datesAuteurs], '', $element->plaintext));

            $this->items[] = $item;
        }
    }

    private function getItemTitle($title, $datesAuteurs)
    {
        $itemTitle = $this->cleanText($title);
        if (strlen($datesAuteurs) > 0) {
            $itemTitle .= ' - ' . $this->cleanText($datesAuteurs);
        }
        return $itemTitle;
    }
}
