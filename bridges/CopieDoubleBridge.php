<?php

class CopieDoubleBridge extends BridgeAbstract
{
    const MAINTAINER = 'superbaillot.net';
    const NAME = 'CopieDouble';
    const URI = 'http://www.copie-double.com/';
    const CACHE_TIMEOUT = 14400; // 4h
    const DESCRIPTION = 'CopieDouble';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI);

        $table = $html->find('table table', 2);

        foreach ($table->find('tr') as $element) {
            $td = $element->find('td', 0);

            if ($td->class === 'couleur_1') {
                $item = [];
                $title = $td->innertext;
                $pos = strpos($title, '<a');
                $title = substr($title, 0, $pos);
                $item['title'] = $title;
            } elseif (strpos($element->innertext, '/images/suivant.gif') === false) {
                $a = $element->find('a', 0);
                $item['uri'] = self::URI . $a->href;
                $content = str_replace('src="/', 'src="/' . self::URI, $element->find('td', 0)->innertext);
                $content = str_replace('href="/', 'href="' . self::URI, $content);
                $item['content'] = $content;
                $this->items[] = $item;
            }
        }
    }
}
