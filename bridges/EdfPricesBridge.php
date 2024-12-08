<?php

class EdfPricesBridge extends BridgeAbstract
{
    const NAME = 'EDF tarifs';
    // pull info from this site for now because EDF do not provide correct opendata
    const URI = 'https://www.jechange.fr';
    const DESCRIPTION = 'Fetches the latest infos of EDF prices';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [
        [
            'contract' => [
                'name' => 'Choisir un contrat',
                'type' => 'list',
                // we can add later HCHP, EJP, base
                'values' => ['Tempo' => '/energie/edf/tarifs/tempo'],
            ]
        ]
    ];
    const CACHE_TIMEOUT = 7200; // 2h

    /**
     * @param simple_html_dom $html
     * @param string $contractUri
     * @return void
     */
    private function tempo(simple_html_dom $html, string $contractUri): void
    {
        // current color and next
        $daysDom = $html->find('#calendrier', 0)->nextSibling()->find('.card--ejp');
        if ($daysDom && count($daysDom) === 2) {
            foreach ($daysDom as $dayDom) {
                $day = trim($dayDom->find('.card__title', 0)->innertext) . '/' . (new \DateTime('now'))->format(('Y'));
                $dayColor = $dayDom->find('.card-ejp__icon span', 0)->innertext;

                $text = $day . ' - ' . $dayColor;
                $item['uri'] = self::URI . $contractUri;
                $item['title'] = $text;
                $item['author'] = self::MAINTAINER;
                $item['content'] = $text;
                $item['uid'] = hash('sha256', $item['title']);

                $this->items[] = $item;
            }
        }

        // colors
        $ulDom = $html->find('#tarif-de-l-offre-tempo-edf-template-date-now-y', 0)->nextSibling()->nextSibling()->nextSibling();
        $elementsDom = $ulDom->find('li');
        if ($elementsDom && count($elementsDom) === 3) {
            foreach ($elementsDom as $elementDom) {
                $item = [];

                $matches = [];
                preg_match_all('/Jour (.*) : Heures (.*) : (.*)&nbsp;€ \/ Heures (.*) : (.*)&nbsp;€/um', $elementDom->innertext, $matches, PREG_SET_ORDER, 0);

                if ($matches && count($matches[0]) === 6) {
                    for ($i = 0; $i < 2; $i++) {
                        $text = 'Jour ' . $matches[0][1] . ' - Heures ' . $matches[0][2 + 2 * $i] . ' : ' . $matches[0][3 + 2 * $i] . '€';
                        $item['uri'] = self::URI . $contractUri;
                        $item['title'] = $text;
                        $item['author'] = self::MAINTAINER;
                        $item['content'] = $text;
                        $item['uid'] = hash('sha256', $item['title']);

                        $this->items[] = $item;
                    }
                }
            }
        }

        // powers
        $ulPowerContract = $ulDom->nextSibling()->nextSibling();
        $elementsPowerContractDom = $ulPowerContract->find('li');
        if ($elementsPowerContractDom && count($elementsPowerContractDom) === 4) {
            foreach ($elementsPowerContractDom as $elementPowerContractDom) {
                $item = [];

                $matches = [];
                preg_match_all('/(.*) kVA : (.*) €/um', $elementPowerContractDom->innertext, $matches, PREG_SET_ORDER, 0);

                if ($matches && count($matches[0]) === 3) {
                    $text = $matches[0][1] . ' kVA : ' . $matches[0][2] . '€';
                    $item['uri'] = self::URI . $contractUri;
                    $item['title'] = $text;
                    $item['author'] = self::MAINTAINER;
                    $item['content'] = $text;
                    $item['uid'] = hash('sha256', $item['title']);

                    $this->items[] = $item;
                }
            }
        }
    }

    public function collectData()
    {
        $contract = $this->getKey('contract');
        $contractUri = $this->getInput('contract');
        $html = getSimpleHTMLDOM(self::URI . $contractUri);

        if ($contract === 'Tempo') {
            $this->tempo($html, $contractUri);
        }
    }
}
