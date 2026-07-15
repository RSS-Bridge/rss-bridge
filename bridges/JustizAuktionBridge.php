<?php

declare(strict_types=1);

class JustizAuktionBridge extends BridgeAbstract
{
    const NAME = 'Justiz-Auktion Neu Eingestellt (Deutscheland & Österreich)';
    const URI         = 'https://www.justiz-auktion.de/neu-eingestellt';
    const DESCRIPTION = 'RSS feed for Justiz-Auktion Neu Eingestellt';
    const MAINTAINER  = 'jummo4@yahoo.de';
    const URI_LINKS   = 'https://www.justiz-auktion.de/';

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(self::URI);
        foreach ($dom->find('ul.auktionen li[id^="rlaid"]') as $entry) {
            $this->items[] = [
                'title' => $entry->find('h5 a', 0)->plaintext,
                'uri' => self::URI_LINKS . $entry->find('h5 a', 0)->href,
                'content' => '<img src="' . self::URI_LINKS . $entry->find('li.image img', 0)->src . '"><br>'
                    . $entry->find('div.paddingRight1em p', 0)->plaintext
                    . '<br> Standort:'
                    . $entry->find('div.clearfix', 0),
            ];
        }
    }
}
