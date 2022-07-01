<?php

class ComboiosDePortugalBridge extends BridgeAbstract
{
    const NAME = 'CP | Avisos';
    const BASE_URI = 'https://www.cp.pt';
    const URI = self::BASE_URI . '/passageiros/pt';
    const DESCRIPTION = 'Comboios de Portugal | Avisos';
    const MAINTAINER = 'somini';

    public function collectData()
    {
        # Do not verify SSL certificate (the server doesn't send the intermediate)
        # https://github.com/RSS-Bridge/rss-bridge/issues/2397
        $html = getSimpleHTMLDOM($this->getURI() . '/consultar-horarios/avisos', [], [
            CURLOPT_SSL_VERIFYPEER => 0,
        ]);

        foreach ($html->find('.warnings-table a') as $element) {
            $item = [];

            $item['title'] = $element->innertext;
            $item['uri'] = self::BASE_URI . implode('/', array_map('urlencode', explode('/', $element->href)));

            $this->items[] = $item;
        }
    }
}
