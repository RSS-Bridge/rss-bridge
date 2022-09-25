<?php

class DiarioDoAlentejoBridge extends BridgeAbstract
{
    const MAINTAINER = 'somini';
    const NAME = 'Diário do Alentejo';
    const URI = 'https://www.diariodoalentejo.pt';
    const DESCRIPTION = 'Semanário Regionalista Independente';
    const CACHE_TIMEOUT = 28800; // 8h

    /* This is used to hack around obtaining a timestamp. It's just a list of Month names in Portuguese ... */
    const PT_MONTH_NAMES = [
        'janeiro',
        'fevereiro',
        'março',
        'abril',
        'maio',
        'junho',
        'julho',
        'agosto',
        'setembro',
        'outubro',
        'novembro',
        'dezembro'];

    public function getIcon()
    {
        return 'https://www.diariodoalentejo.pt/images/favicon/apple-touch-icon.png';
    }

    public function collectData()
    {
        /* This is slow as molasses (>30s!), keep the cache timeout high to avoid killing the host */
        $html = getSimpleHTMLDOMCached($this->getURI() . '/pt/noticias-listagem.aspx');

        foreach ($html->find('.list_news .item') as $element) {
            $item = [];

            $item_link = $element->find('.body h2.title a', 0);
            /* Another broken URL, see also `bridges/ComboiosDePortugalBridge.php` */
            $item['uri'] = self::URI . implode('/', array_map('urlencode', explode('/', $item_link->href)));
            $item['title'] = $item_link->innertext;

            $item['timestamp'] = str_ireplace(
                array_map(function ($name) {
                    return ' ' . $name . ' ';
                }, self::PT_MONTH_NAMES),
                array_map(function ($num) {
                    return sprintf('-%02d-', $num);
                }, range(1, sizeof(self::PT_MONTH_NAMES))),
                $element->find('span.date', 0)->innertext
            );

            /* Fix the Image URL */
            $item_image = $element->find('img.thumb', 0);
            $item_image->src = preg_replace('/.*&img=([^&]+).*/', '\1', $item_image->getAttribute('data-src'));

            /* Content: */
            /* - Image */
            /* - Category */
            $content = $item_image .
                '<center>' . $element->find('a.category', 0) . '</center>';
            $item['content'] = defaultLinkTo($content, self::URI);

            $this->items[] = $item;
        }
    }
}
