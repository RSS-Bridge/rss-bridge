<?php

declare(strict_types=1);

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
                'defaultValue' => 'base',
                // Stable, short values (first entry of each group): they are part of
                // every subscriber's saved feed URL and must never change, even if the
                // site we scrape (see CONTRACTS below) moves again. The other entries
                // in each group are values this bridge used in the past (see
                // LEGACY_CONTRACTS) kept here only so RSS-Bridge's own parameter
                // validation still accepts them instead of silently discarding them.
                'values' => [
                    'Base' => [
                        'Base' => 'base',
                        'Base (lien historique)' => '/energie/edf/tarifs/tarif-bleu#base',
                    ],
                    'HPHC' => [
                        'HPHC' => 'hphc',
                        'HPHC (lien historique)' => '/energie/edf/tarifs/tarif-bleu#hphc',
                    ],
                    'EJP' => [
                        'EJP' => 'ejp',
                        'EJP (lien historique)' => '/energie/edf/tarifs/tarif-bleu#ejp',
                    ],
                    'Tempo' => [
                        'Tempo' => 'tempo',
                        'Tempo (lien historique)' => '/energie/edf/tarifs/tempo',
                    ],
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

    // Where to find each contract's pricing table right now. Only this map needs
    // updating if jechange.fr reshuffles its pages/anchors again; the "contract"
    // values above (and thus everyone's saved feed URLs) stay untouched.
    const CONTRACTS = [
        'base' => [
            'label' => 'Base',
            'path' => '/energie/edf/tarifs/tarifs-reglementes',
            'heading' => 'option-base',
        ],
        'hphc' => [
            'label' => 'HPHC',
            'path' => '/energie/edf/tarifs/tarifs-reglementes',
            'heading' => 'option-heures-pleines-heures-creuses',
        ],
        'ejp' => [
            'label' => 'EJP',
            'path' => '/energie/edf/tarifs/tarifs-reglementes',
            'heading' => 'option-ejp',
        ],
        'tempo' => [
            'label' => 'Tempo',
            'path' => '/energie/edf/tarifs/tempo',
            'heading' => 'option-tempo',
        ],
    ];

    // Raw "contract" values used by this bridge in the past, kept working so that
    // feed URLs generated before a site restructuring don't break.
    const LEGACY_CONTRACTS = [
        '/energie/edf/tarifs/tarif-bleu#base' => 'base',
        '/energie/edf/tarifs/tarif-bleu#hphc' => 'hphc',
        '/energie/edf/tarifs/tarif-bleu#ejp' => 'ejp',
        '/energie/edf/tarifs/tempo' => 'tempo',
    ];

    // The header label used for the subscription (yearly) column, as opposed to
    // the per-kWh rate columns (whose count and labels vary per contract).
    const SUBSCRIPTION_LABEL = 'Abonnement';

    public function collectData()
    {
        $rawContract = $this->getInput('contract');
        $power = (int) $this->getInput('power');

        if (isset(self::CONTRACTS[$rawContract])) {
            $contractKey = $rawContract;
        } else {
            $contractKey = self::LEGACY_CONTRACTS[$rawContract] ?? null;
        }
        $contract = $contractKey ? self::CONTRACTS[$contractKey] : null;

        if (!$contract) {
            throwServerException('Contrat EDF inconnu: "' . $rawContract . '".');
        }

        $contractUri = $contract['path'] . '#' . $contract['heading'];
        $html = getSimpleHTMLDOM(self::URI . $contract['path']);

        $headingDom = $html->find('#' . $contract['heading'], 0);
        if (!$headingDom) {
            throwServerException(
                sprintf(
                    'Impossible de trouver la section "%s" sur la page EDF. Le site a probablement encore changé de structure.',
                    $contract['label']
                )
            );
        }

        $tableDom = $this->findPriceTableAfter($html, $headingDom);
        if (!$tableDom) {
            throwServerException(
                sprintf(
                    'Impossible de trouver le tableau de tarifs pour "%s" sur la page EDF. Le site a probablement encore changé de structure.',
                    $contract['label']
                )
            );
        }

        $this->addItemsFromTable($tableDom, $contractUri, $power);
    }

    /**
     * Finds the first <table> appearing after $headingDom (in document order)
     * whose first column header is "Puissance". Locating it this way, instead of
     * hard-coding a number of DOM hops from the heading, survives changes to the
     * markup nesting around the table.
     */
    private function findPriceTableAfter(simple_html_dom $html, simple_html_dom_node $headingDom): ?simple_html_dom_node
    {
        foreach ($html->find('table') as $tableDom) {
            if ($tableDom->tag_start <= $headingDom->tag_start) {
                continue;
            }
            $firstHeader = $tableDom->find('thead th', 0);
            if ($firstHeader && trim($firstHeader->plaintext) === 'Puissance') {
                return $tableDom;
            }
        }
        return null;
    }

    private function addItemsFromTable(simple_html_dom_node $tableDom, string $contractUri, int $power): void
    {
        $headers = array_map(fn($th) => trim($th->plaintext), $tableDom->find('thead th'));

        foreach ($tableDom->find('tbody tr') as $row) {
            $cells = $row->find('td');
            if (!$cells) {
                continue;
            }

            if ((int) $this->cellText($cells[0]) !== $power) {
                continue;
            }

            foreach ($cells as $i => $cell) {
                if ($i === 0 || !isset($headers[$i])) {
                    continue;
                }

                $label = $headers[$i];
                $value = $this->cellText($cell);
                if ($value === '') {
                    continue;
                }
                if ($label === self::SUBSCRIPTION_LABEL) {
                    $value .= '/an';
                }

                $this->addItem($label . ' : ' . $value, $contractUri);
            }

            return;
        }

        $this->addItem('Pas de tarif disponible pour cette puissance et ce contrat', $contractUri);
    }

    // Cells hold a value span (e.g. "144,36 €" or "6") optionally followed by
    // extra decorative markup (units, tooltips). Reading only the first span
    // avoids picking up that extra content.
    private function cellText(simple_html_dom_node $cell): string
    {
        $span = $cell->find('span', 0);
        $text = $span ? $span->plaintext : $cell->plaintext;
        return trim(str_replace("\xc2\xa0", ' ', html_entity_decode($text, ENT_QUOTES)));
    }

    private function addItem(string $text, string $contractUri): void
    {
        $item = [];
        $item['uri'] = self::URI . $contractUri;
        $item['title'] = $text;
        $item['author'] = self::MAINTAINER;
        $item['content'] = $text;
        $item['uid'] = hash('sha256', $item['title']);

        $this->items[] = $item;
    }
}
