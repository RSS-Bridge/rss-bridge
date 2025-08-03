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

    const OFFER_LOW_PRIORITIES = [
        'Imbiss nach der Blutspende',
        'Registrierung als Stammzellspender',
        'Typisierung möglich!',
        'Allgemeine Informationen',
        'Krankenkassen belohnen Blutspender',
        'Wer benötigt eigentlich eine Blutspende?',
        'Win-Win-Situation für die Gesundheit!',
        'Terminreservierung',
        'Du möchtest das erste Mal Blut spenden?',
        'Spende-Check',
        'Sie haben Fragen vor Ihrer Blutspende?'
    ];

    const IMAGE_PRIORITIES = [
        'DRK',
        'Imbiss',
        'Obst',
    ];

    public function collectData()
    {
        $limitItems = intval($this->getInput('limit_items'));
        $this->collectExpandableDatas(self::buildAppointmentsURI(), $limitItems);
    }

    protected function parseItem(array $item)
    {
        $html = getSimpleHTMLDOMCached($item['uri']);

        $detailsElement = $html->find('.details', 0);

        $dateLines = self::explodeLines($detailsElement->find('.datum', 0)->plaintext);
        $addressLines = self::explodeLines($detailsElement->find('.adresse', 0)->plaintext);

        $infoElement = $detailsElement->find('.angebote > h4 + p', 0);
        $info = $infoElement ? trim($infoElement->plaintext) : '';

        $offers = self::parseOffers($detailsElement->find('.angebote .item'));

        $images = self::parseImages($detailsElement->find('.fotos', 0));
        usort($images, function ($imageA, $imageB): int {
            list($titleA) = $imageA;
            list($titleB) = $imageB;
            $prioA = 0;
            $prioB = 0;
            foreach (self::IMAGE_PRIORITIES as $prioIndex => $prioTitleNeedle) {
                if (stripos($titleA, $prioTitleNeedle) !== false) {
                    $prioA = $prioIndex + 1;
                }
                if (stripos($titleB, $prioTitleNeedle) !== false) {
                    $prioB = $prioIndex + 1;
                }
            }
            return $prioA - $prioB;
        });

        $itemContent = <<<HTML
        <div>
            <p>
                <b>{$dateLines[0]} {$dateLines[1]}</b><br>
                {$addressLines[3]}
            </p>
            <p>
                <b>{$addressLines[0]}</b><br>
                {$addressLines[1]}<br>
                {$addressLines[2]}
            </p>
        </div>
        HTML;

        if ($info) {
            $itemContent .= <<<HTML
            <div>
                <h3>Infos</h3>
                <p>{$info}</p>
            </div>
            HTML;
        }

        $majorOffers = array_filter($offers, fn($title): bool => !in_array($title, self::OFFER_LOW_PRIORITIES), ARRAY_FILTER_USE_KEY);
        foreach ($majorOffers as $offerTitle => list($offerText, $offerImages)) {
            $itemContent .= <<<HTML
            <div>
                <h3>{$offerTitle}</h3>
                <p>{$offerText}</p>
            HTML;
            foreach ($offerImages as list($imageTitle, $imageUrl)) {
                $itemContent .= <<<HTML
                <figure>
                    <img src="{$imageUrl}">
                    <figcaption>{$imageTitle}</figcaption>
                </figure>
                HTML;
            }
            $itemContent .= <<<HTML
            </div>
            HTML;
        }

        if (count($images) > 0) {
            $itemContent .= <<<HTML
            <div>
                <h3>Fotos</h3>
            HTML;
            foreach ($images as list($imageTitle, $imageUrl)) {
                $itemContent .= <<<HTML
                <figure>
                    <img src="{$imageUrl}">
                    <figcaption>{$imageTitle}</figcaption>
                </figure>
                HTML;
            }
            $itemContent .= <<<HTML
            </div>
            HTML;
        }

        $minorOffers = array_filter($offers, fn($title): bool => in_array($title, self::OFFER_LOW_PRIORITIES), ARRAY_FILTER_USE_KEY);
        foreach ($minorOffers as $offerTitle => list($offerText)) {
            $itemContent .= <<<HTML
            <div>
                <h3>{$offerTitle}</h3>
                <p>{$offerText}</p>
            </div>
            HTML;
        }

        $item['title'] = $dateLines[0] . ' ' . $dateLines[1] . ' ' . $addressLines[0] . ' - ' . $addressLines[1];
        $item['content'] = $itemContent;
        $item['description'] = null;
        $item['enclosures'] = array_map(
            function ($image): string {
                list($title, $url) = $image;
                return $url . '#' . urlencode(str_replace(' ', '_', $title));
            },
            $images
        );

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

    private function parseImages($parentElement): array
    {
        $images = [];

        if ($parentElement) {
            $elements = $parentElement->find('a[data-lightbox]');
            foreach ($elements as $i => $element) {
                $url = trim($element->getAttribute('href'));
                if (!$url) {
                    continue;
                }

                $title = trim($element->getAttribute('title'));
                if (!$title) {
                    $number = $i + 1;
                    $title = "Foto {$number}";
                }

                $images[] = [$title, $url];
            }
        }

        return $images;
    }

    private function parseOffers($offerElements): array
    {
        $offers = [];

        foreach ($offerElements as $element) {
            $title = self::getCleanPlainText($element->find(':is(h1,h2,h3,h4,h5,h6)', 0));
            $text = trim(substr(self::getCleanPlainText($element), strlen($title)));
            if (!$title || !$text) {
                continue;
            }

            $linkElements = $element->find('a');
            foreach ($linkElements as $linkElement) {
                $linkText = trim($linkElement->plaintext);
                $linkUrl = trim($linkElement->getAttribute('href'));
                if (!$linkText || !$linkUrl) {
                    continue;
                }

                $linkHtml = <<<HTML
                <a href="{$linkUrl}" target="_blank">{$linkText}</a>
                HTML;
                $text = str_replace($linkText, $linkHtml, $text);
            }

            $offers[$title] = [$text, self::parseImages($element)];
        }

        return $offers;
    }

    private function getCleanPlainText($htmlElement): string
    {
        return $htmlElement ? trim(preg_replace('/\s+/', ' ', html_entity_decode($htmlElement->plaintext))) : '';
    }

    /**
     * Returns an array of strings, each of which is a substring of string formed by splitting it on boundaries formed by line breaks.
     */
    private function explodeLines(string $text): array
    {
        return array_map('trim', preg_split('/(\s*(\r\n|\n|\r)\s*)+/', $text));
    }
}
