<?php

class ViadeoCompanyBridge extends BridgeAbstract
{
    const MAINTAINER = 'regisenguehard';
    const NAME = 'Viadeo Company';
    const URI = 'https://www.viadeo.com/';
    const CACHE_TIMEOUT = 21600; // 6h
    const DESCRIPTION = 'Returns most recent actus from Company on Viadeo.
 (http://www.viadeo.com/fr/company/<strong style="font-weight:bold;">apple</strong>)';

    const PARAMETERS = [ [
        'c' => [
            'name' => 'Company name',
            'exampleValue' => 'apple',
            'required' => true
        ]
    ]];

    public function collectData()
    {
        // Redirects to https://emploi.lefigaro.fr/recherche/entreprises
        $url = sprintf('%sfr/company/%s', self::URI, $this->getInput('c'));

        $html = getSimpleHTMLDOM($url);

        // TODO: Fix broken xpath selector
        $elements = $html->find('//*[@id="company-newsfeed"]/ul/li');

        foreach ($elements as $element) {
            $title = $element->find('p', 0)->innertext;
            if (!$title) {
                continue;
            }
            $item = [];
            $item['uri'] = $url;
            $item['title'] = mb_substr($element->find('p', 0)->innertext, 0, 100);
            $item['content'] = $element->find('p', 0)->innertext;
            ;
            $this->items[] = $item;
        }
    }
}
