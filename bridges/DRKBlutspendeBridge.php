<?php

class DRKBlutspendeBridge extends FeedExpander
{
    const MAINTAINER = 'User123698745';
    const NAME = 'DRK-Blutspende';
    const BASE_URI = 'https://www.drk-blutspende.de';
    const URI = self::BASE_URI;
    const CACHE_TIMEOUT = 60 * 60 * 1; // 1 hour
    const DESCRIPTION = 'German Red Cross (Deutsches Rotes Kreuz) blood donation service feed with more details';
    const CONTEXT_APPOINTMENTS = 'Termine';
    const PARAMETERS = [
        self::CONTEXT_APPOINTMENTS => [
            'term' => [
                'name' => 'PLZ / Ort',
                'required' => true,
                'exampleValue' => '12555',
            ],
            'radius' => [
                'name' => 'Umkreis in km',
                'type' => 'number',
                'exampleValue' => 10,
            ],
            'limit_days' => [
                'name' => 'Limit von Tagen',
                'title' => 'Nur Termine innerhalb der nächsten x Tagen',
                'type' => 'number',
                'exampleValue' => 28,
            ],
            'limit_items' => [
                'name' => 'Limit von Terminen',
                'title' => 'Nicht mehr als x Termine',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 20,
            ]
        ]
    ];

    public function collectData()
    {
        $limitItems = intval($this->getInput('limit_items'));
        $this->collectExpandableDatas(self::buildAppointmentsURI(), $limitItems);
    }

    protected function parseItem(array $item)
    {
        $html = getSimpleHTMLDOM($item['uri']);

        $detailsElement = $html->find('.details', 0);

        $dateElement = $detailsElement->find('.datum', 0);
        $dateLines = self::explodeLines($dateElement->plaintext);

        $addressElement = $detailsElement->find('.adresse', 0);
        $addressLines = self::explodeLines($addressElement->plaintext);

        $infoElement = $detailsElement->find('.angebote > h4 + p', 0);
        $info = $infoElement ? $infoElement->innertext : '';

        $imageElements = $detailsElement->find('.fotos img');

        $item['title'] = $dateLines[0] . ' ' . $dateLines[1] . ' ' . $addressLines[0] . ' - ' . $addressLines[1];

        $item['content'] = <<<HTML
        <p><b>{$dateLines[0]} {$dateLines[1]}</b></p>
        <p>{$addressElement->innertext}</p>
        <p>{$info}</p>
        HTML;

        foreach ($imageElements as $imageElement) {
            $src = $imageElement->getAttribute('src');
            $item['content'] .= <<<HTML
            <p><img src="{$src}"></p>
            HTML;
        }

        $item['description'] = null;

        return $item;
    }

    public function getURI()
    {
        if ($this->queriedContext === self::CONTEXT_APPOINTMENTS) {
            return str_replace('.rss?', '?', self::buildAppointmentsURI());
        }
        return parent::getURI();
    }

    private function buildAppointmentsURI()
    {
        $term = $this->getInput('term') ?? '';
        $radius = $this->getInput('radius') ?? '';
        $limitDays = intval($this->getInput('limit_days'));
        $dateTo = $limitDays > 0 ? date('Y-m-d', time() + (60 * 60 * 24 * $limitDays)) : '';
        return self::BASE_URI . '/blutspendetermine/termine.rss?date_to=' . $dateTo . '&radius=' . $radius . '&term=' . $term;
    }

    /**
     * Returns an array of strings, each of which is a substring of string formed by splitting it on boundaries formed by line breaks.
     */
    private function explodeLines(string $text): array
    {
        return array_map('trim', preg_split('/(\s*(\r\n|\n|\r)\s*)+/', $text));
    }
}
