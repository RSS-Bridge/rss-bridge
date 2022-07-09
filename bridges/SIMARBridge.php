<?php

class SIMARBridge extends BridgeAbstract
{
    const NAME = 'SIMAR';
    const URI = 'http://www.simar-louresodivelas.pt/';
    const DESCRIPTION = 'Verificar estado da rede SIMAR';
    const MAINTAINER = 'somini';
    const PARAMETERS = [
        'Público' => [
            'interventions' => [
                'type' => 'checkbox',
                'name' => 'Incluir Intervenções?',
                'defaultValue' => 'checked',
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $e_home = $html->find('#home', 0)
            or returnServerError('Invalid site structure');

        foreach ($e_home->find('span') as $element) {
            $item = [];

            $item['title'] = 'Rotura: ' . $element->plaintext;
            $item['content'] = $element->innertext;
            $item['uid'] = 'urn:sha1:' . hash('sha1', $item['content']);

            $this->items[] = $item;
        }

        if ($this->getInput('interventions')) {
            $e_main1 = $html->find('#menu1', 0)
                or returnServerError('Invalid site structure');

            foreach ($e_main1->find('a') as $element) {
                $item = [];

                $item['title'] = 'Intervenção: ' . $element->plaintext;
                $item['uri'] = $this->getURI() . $element->href;
                $item['content'] = $element->innertext;

                /* Try to get the actual contents for this kind of item */
                $item_html = getSimpleHTMLDOMCached($item['uri']);
                if ($item_html) {
                    $e_item = $item_html->find('.auto-style59', 0);
                    foreach ($e_item->find('p') as $paragraph) {
                        /* Remove empty paragraphs */
                        if (preg_match('/^(\W|&nbsp;)+$/', $paragraph->innertext) == 1) {
                            $paragraph->outertext = '';
                        }
                    }
                    if ($e_item) {
                        $item['content'] = $e_item->innertext;
                    }
                }

                $this->items[] = $item;
            }
        }
    }
}
