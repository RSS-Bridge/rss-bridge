<?php

class PresidenciaPTBridge extends BridgeAbstract
{
    const NAME = 'Presidência da República Portuguesa';
    const URI = 'https://www.presidencia.pt';
    const DESCRIPTION = 'Presidência da República Portuguesa';
    const MAINTAINER = 'somini';
    const PARAMETERS = [
        'Section' => [
            '/atualidade/noticias' => [
                'name' => 'Notícias',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
            ],
            '/atualidade/mensagens' => [
                'name' => 'Mensagens',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
            ],
            '/atualidade/atividade-legislativa' => [
                'name' => 'Atividade Legislativa',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
            ],
            '/atualidade/notas-informativas' => [
                'name' => 'Notas Informativas',
                'type' => 'checkbox',
                'defaultValue' => 'checked',
            ]
        ]
    ];

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
        return 'https://www.presidencia.pt/Theme/favicon/apple-touch-icon.png';
    }

    public function collectData()
    {
        $contexts = $this->getParameters();

        foreach (array_keys($contexts['Section']) as $k) {
            if ($this->getInput($k)) {
                $html = getSimpleHTMLDOMCached($this->getURI() . $k);

                foreach ($html->find('#atualidade-list article.card-block') as $element) {
                    $item = [];

                    $link = $element->find('a', 0);
                    $etitle = $element->find('.article-title', 0);
                    $edts = $element->find('.date', 0);
                    $edt = $edts->innertext;

                    $item['title'] = strip_tags($etitle->innertext);
                    $item['uri'] = self::URI . $link->href;
                    $item['description'] = $element;
                    $item['timestamp'] = str_ireplace(
                        array_map(function ($name) {
                            return ' de ' . $name . ' de ';
                        }, self::PT_MONTH_NAMES),
                        array_map(function ($num) {
                            return sprintf('-%02d-', $num);
                        }, range(1, count(self::PT_MONTH_NAMES))),
                        $edt
                    );

                    $this->items[] = $item;
                }
            }
        }
    }
}
