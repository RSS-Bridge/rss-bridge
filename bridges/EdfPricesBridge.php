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
                // we can add later more option prices
                'values' => [
                    'Base' => '/energie/edf/tarifs/tarif-bleu#base',
                    'HPHC' => '/energie/edf/tarifs/tarif-bleu#hphc',
                    'EJP' => '/energie/edf/tarifs/tarif-bleu#ejp',
                    'Tempo' => '/energie/edf/tarifs/tempo'
                ],
            ],
            'power' => [
                'name' => 'Choisir une puissance',
                'type' => 'list',
                'values' => [
                    '3 kVA' => 3,
                    '6 kVA' => 6,
                    '9 kVA' => 9,
                    '12 kVA' => 12,
                    '15 kVA' => 15,
                    '18 kVA' => 18,
                    '24 kVA' => 24,
                    '30 kVA' => 30,
                    '36 kVA' => 36
                ]
            ]
        ]
    ];
    const CACHE_TIMEOUT = 7200; // 2h

    /**
     * @param simple_html_dom $html
     * @param string $contractUri
     * @return void
     */
    private function tempo(simple_html_dom $html, string $contractUri, int $power): void
    {
        // colors
        $ulDom = $html->find('#tarif-de-l-offre-tempo-edf-template-date-now-y', 0)->nextSibling()->nextSibling()->nextSibling();
        $elementsDom = $ulDom->find('li');
        if ($elementsDom && count($elementsDom) === 3) {
            // price per kWh is same for all powers
            foreach ($elementsDom as $elementDom) {
                $item = [];

                $matches = [];
                preg_match_all('/Jour (.*) : Heures (.*) : (.*)&nbsp;€ \/ Heures (.*) : (.*)&nbsp;€/um', $elementDom->innertext, $matches, PREG_SET_ORDER, 0);

                // for tempo contract we have 2x3 colors
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

        // add subscription power info
        $tablePrices = $ulDom->nextSibling()->nextSibling()->nextSibling()->find('.table--responsive', 0);
        $this->addSubscriptionPowerInfo($tablePrices, $contractUri, $power, 7);
    }

    /**
     * @param simple_html_dom $html
     * @param string $contractUri
     * @return void
     */
    private function base(simple_html_dom $html, string $contractUri, int $power): void
    {
        $tablePrices = $html
                            ->find('#grille-tarifaire-et-prix-du-kwh-du-tarif-reglemente-edf-en-option-base', 0)
                            ->nextSibling()
                            ->nextSibling()
                            ->nextSibling();

        $prices = $tablePrices->find('.table--stripped tbody tr');
        // last element is useless because part of another table
        array_pop($prices);

        // price per kWh is same for all powers
        if ($prices && count($prices) === 9) {
            $item = [];

            $text = 'Base : ' . $prices[0]->children(2);
            $item['uri'] = self::URI . $contractUri;
            $item['title'] = $text;
            $item['author'] = self::MAINTAINER;
            $item['content'] = $text;
            $item['uid'] = hash('sha256', $item['title']);

            $this->items[] = $item;
        }

        $this->addSubscriptionPowerInfo($tablePrices, $contractUri, $power, 9);
    }

    /**
     * @param simple_html_dom $html
     * @param string $contractUri
     * @return void
     */
    private function hphc(simple_html_dom $html, string $contractUri, int $power): void
    {
        $tablePrices = $html
                            ->find('#grille-tarifaire-et-prix-du-kwh-du-tarif-reglemente-edf-en-option-heures-pleines-heures-creuses', 0)
                            ->nextSibling()
                            ->nextSibling()
                            ->nextSibling();

        $prices = $tablePrices->find('.table--stripped tbody tr');
        // last element is useless because part of another table
        array_pop($prices);

        // price per kWh is same for all powers
        if ($prices && count($prices) === 8) {
            $values = ['HC', 'HP'];
            foreach ($values as $key => $value) {
                $i++;
                $item = [];

                $text = $values[$key] . ' : ' . $prices[0]->children($key + 2);
                $item['uri'] = self::URI . $contractUri;
                $item['title'] = $text;
                $item['author'] = self::MAINTAINER;
                $item['content'] = $text;
                $item['uid'] = hash('sha256', $item['title']);

                $this->items[] = $item;
            }
        }

        $this->addSubscriptionPowerInfo($tablePrices, $contractUri, $power, 8);
    }

    /**
     * @param simple_html_dom $html
     * @param string $contractUri
     * @return void
     */
    private function ejp(simple_html_dom $html, string $contractUri, int $power): void
    {
        $tablePrices = $html
                            ->find('#grille-tarifaire-et-prix-du-kwh-du-tarif-reglemente-edf-en-option-ejp', 0)
                            ->nextSibling()
                            ->nextSibling()
                            ->nextSibling();

        $prices = $tablePrices->find('.table--stripped tbody tr');
        // last element is useless because part of another table
        array_pop($prices);

        // price per kWh is same for all powers
        if ($prices && count($prices) === 5) {
            $values = ['Non EJP', 'EJP'];
            foreach ($values as $key => $value) {
                $i++;
                $item = [];

                $text = $values[$key] . ' : ' . $prices[0]->children($key + 2);
                $item['uri'] = self::URI . $contractUri;
                $item['title'] = $text;
                $item['author'] = self::MAINTAINER;
                $item['content'] = $text;
                $item['uid'] = hash('sha256', $item['title']);

                $this->items[] = $item;
            }
        }

        $this->addSubscriptionPowerInfo($tablePrices, $contractUri, $power, 5);
    }

    private function addSubscriptionPowerInfo(simple_html_dom_node $tablePrices, string $contractUri, int $power, int $numberOfPrices): void
    {
        $prices = $tablePrices->find('.table--stripped tbody tr');
        // last element is useless because part of another table
        array_pop($prices);

        // 7 contracts for tempo: 6, 9, 12, 15, 18, 30 and 36 kVA
        // 9 contracts for base: 3, 6, 9, 12, 15, 18, 24, 30 and 36 kVA
        // 7 contracts for HPHC: 6, 9, 12, 15, 18, 24, 30 and 36 kVA
        // 5 contracts for EJP: 9, 12, 15, 18 and 36 kVA
        if ($prices && count($prices) === $numberOfPrices) {
            $powerFound = false;
            foreach ($prices as $price) {
                $powerText = $price->firstChild()->firstChild()->innertext;
                $powerValue = (int)substr($powerText, 0, strpos($powerText, ' kVA'));

                if ($powerValue !== $power) {
                    continue;
                }

                $item = [];

                $text = $powerText . ' : ' . $price->children(1) . '/an';
                $item['uri'] = self::URI . $contractUri;
                $item['title'] = $text;
                $item['author'] = self::MAINTAINER;
                $item['content'] = $text;
                $item['uid'] = hash('sha256', $item['title']);

                $this->items[] = $item;
                $powerFound = true;
                break;
            }

            if (!$powerFound) {
                $item = [];

                $text = 'Pas de tarif abonnement pour cette puissance et ce contrat';
                $item['uri'] = self::URI . $contractUri;
                $item['title'] = $text;
                $item['author'] = self::MAINTAINER;
                $item['content'] = $text;
                $item['uid'] = hash('sha256', $item['title']);

                $this->items[] = $item;
            }
        }
    }

    public function collectData()
    {
        $contract = $this->getKey('contract');
        $contractUri = $this->getInput('contract');
        $power = $this->getInput('power');
        $html = getSimpleHTMLDOM(self::URI . $contractUri);

        if ($contract === 'Tempo') {
            $this->tempo($html, $contractUri, $power);
        }

        if ($contract === 'Base') {
            $this->base($html, $contractUri, $power);
        }

        if ($contract === 'HPHC') {
            $this->hphc($html, $contractUri, $power);
        }

        if ($contract === 'EJP') {
            $this->ejp($html, $contractUri, $power);
        }
    }
}
