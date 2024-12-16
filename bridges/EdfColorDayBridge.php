<?php

class EdfColorDayBridge extends BridgeAbstract
{
    const NAME = 'EDF tempo color';
    // pull info from this site for now because EDF do not provide correct opendata
    const URI = 'https://www.services-rte.com/cms/open_data/v1/tempo';
    const DESCRIPTION = 'Get EDF color of today and tomorrow of tempo contract';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [
        [
            'contract' => [
                'name' => 'Choisir un contrat',
                'type' => 'list',
                // we can add later more option prices like EJP
                'values' => [
                    'Tempo' => 'tempo'
                ],
            ]
        ]
    ];
    const CACHE_TIMEOUT = 7200; // 2h

    /**
     * @param simple_html_dom $html
     * @param string $contractUri
     * @return void
     */
    private function tempo(string $json): void
    {
        $jsonDecoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $values = [
            $this->formatFrenchDate('now') => date('Y-m-d'),
            'Demain ' . $this->formatFrenchDate('tomorrow') => date('Y-m-d', strtotime('+1 day'))
        ];

        foreach ($values as $key => $value) {
            $i++;
            $item = [];

            $text = $key . ' : ' . $this->getDisplayableColor($jsonDecoded['values'][$value]);
            $item['uri'] = self::URI . $contractUri;
            $item['title'] = $text;
            $item['author'] = self::MAINTAINER;
            $item['content'] = $text;
            $item['uid'] = hash('sha256', $item['title']);

            $this->items[] = $item;
        }
    }

    private function formatFrenchDate(string $datetime): string
    {
        // Set the locale to French
        setlocale(LC_TIME, 'fr_FR.UTF-8');

        // Create a DateTime object for the desired date
        $now = new DateTime($datetime);

        // Format the date
        return strftime('%A %d %B %Y', $now->getTimestamp());
    }

    private function getDisplayableColor(string $color): string
    {
        $displayableColor = null;
        switch ($color) {
            case 'BLUE':
                $displayableColor = '🟦 TEMPO_BLEU';
                break;
            case 'WHITE':
                $displayableColor = '⬜ TEMPO_BLANC';
                break;
            case 'RED':
                $displayableColor = '🟥 TEMPO_ROUGE';
                break;
            default:
                $displayableColor = '⬛ NON_DEFINI';
                break;
        }

        return $displayableColor;
    }

    private function getTempoYear(): string
    {
        $month = date('n'); // Current month as a number (1-12)
        $year = date('Y');  // Current year

        // Assuming the tempo year starts in September
        if ($month >= 9) {
            return $year . '-' . ($year + 1); // e.g., 2024-2025
        }

        return ($year - 1) . '-' . $year; // e.g., 2023-2024
    }

    public function collectData()
    {
        $contract = $this->getKey('contract');

        $header = [
            'Content-type: application/json',
        ];
        $opts = [
            CURLOPT_HTTPGET => 1,
        ];

        $json = getContents(self::URI . '?season=' . $this->getTempoYear(), $header, $opts);

        if ($contract === 'Tempo') {
            $this->tempo($json);
        }
    }
}
