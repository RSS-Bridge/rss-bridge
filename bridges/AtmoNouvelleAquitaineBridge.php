<?php

declare(strict_types=1);

class AtmoNouvelleAquitaineBridge extends BridgeAbstract
{
    const NAME = 'Atmo Nouvelle Aquitaine';
    const URI = 'https://www.atmo-nouvelleaquitaine.org';
    const DESCRIPTION = 'Fetches the latest air polution of cities in Nouvelle Aquitaine from Atmo';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [[
        'city' => [
            'name' => 'Ville',
            'required' => true,
            'exampleValue' => 'bordeaux'
        ]
    ]];
    const CACHE_TIMEOUT = 7200; // 2h

    // Lists every commune of the region as { properties: { nom, code } , ... }.
    // Cached 14 days by the site itself, so resolving a city name only means an
    // actual download on the first request.
    const COMMUNES_URI = 'https://www.atmo-nouvelleaquitaine.org/sites/nouvelleaquitaine/files/geojsons/communes/communes_500_siam_7.geojson';

    public function collectData()
    {
        $city = $this->resolveCity(trim($this->getInput('city')));
        // Commune names can contain spaces/accents (e.g. "La Rochelle"); the site
        // only actually looks at the INSEE code, but the URL still needs to be
        // well-formed.
        $citySlug = implode('/', array_map('rawurlencode', explode('/', $city)));
        $airUri = self::URI . '/air-commune/' . $citySlug . '/indice-atmo';
        $pollenUri = self::URI . '/air-commune/' . $citySlug . '/pollen';

        $message = $this->getMessageForToday($airUri);
        $message .= ' ' . $this->getMessageForTomorrow($airUri);
        $message .= ' ' . $this->getPollenMessage($pollenUri);

        $item['uri'] = $airUri;
        $today = date('d/m/Y');

        $item['title'] = "Bulletin de l'air du $today pour la région Nouvelle Aquitaine.";
        $item['title'] .= ' Retrouvez plus d\'informations en allant sur atmo-nouvelleaquitaine.org #QualiteAir. ' . $message;
        $item['author'] = self::MAINTAINER;
        $item['content'] = $message;
        $item['uid'] = hash('sha256', $item['title']);

        $this->items[] = $item;
    }

    private function getMessageForToday(string $uri): string
    {
        $html = getSimpleHTMLDOM($uri);

        $gaugeDom = $html->find('#indice-gauge .c-gauge-title', 0);
        if (!$gaugeDom) {
            throwServerException(
                'Impossible de trouver l\'indice de qualité de l\'air pour cette ville. Le site a probablement changé de structure.'
            );
        }

        $message = 'La qualité de l\'air est ' . $this->cleanText($gaugeDom->innertext) . '.';
        $polluantMessage = $this->getMessagePolluant($html);
        if ($polluantMessage !== '') {
            $message .= ' ' . $polluantMessage;
        }

        return $message;
    }

    private function getMessageForTomorrow(string $uri): string
    {
        $tomorrow = (new \DateTime('tomorrow'))->format('Y-m-d');
        $html = getSimpleHTMLDOM($uri . '?date=' . $tomorrow);

        $gaugeDom = $html->find('#indice-gauge .c-gauge-title', 0);
        if (!$gaugeDom) {
            throwServerException(
                'Impossible de trouver l\'indice de qualité de l\'air de demain pour cette ville. Le site a probablement changé de structure.'
            );
        }

        $message = 'La qualité de l\'air pour demain sera ' . $this->cleanText($gaugeDom->innertext) . '.';
        $polluantMessage = $this->getMessagePolluant($html);
        if ($polluantMessage !== '') {
            $message .= ' ' . $polluantMessage;
        }

        return $message;
    }

    private function getMessagePolluant(\simple_html_dom $html): string
    {
        $parts = [];
        foreach ($html->find('.c-indice-polluant') as $polluant) {
            $titleDom = $polluant->find('.c-indice-polluant-title', 0);
            $qualificatifDom = $polluant->find('.home-map-legend-item span', 0);
            if (!$titleDom || !$qualificatifDom) {
                continue;
            }

            // The polluant's short code (e.g. "PM2,5") is a nested <span> inside
            // the title; strip it out to get the plain name, then re-append it.
            $codeDom = $titleDom->find('span', 0);
            $name = $this->cleanText(str_replace($codeDom ? $codeDom->outertext : '', '', $titleDom->innertext));
            $code = $codeDom ? trim($codeDom->plaintext) : '';

            $label = $code !== '' ? "$name ($code)" : $name;
            $qualificatif = $this->cleanText($qualificatifDom->plaintext);
            $parts[] = "$label : $qualificatif";
        }

        return $parts ? implode('; ', $parts) . '.' : '';
    }

    /**
     * Reads today's and tomorrow's pollen risk from the page's own settings
     * JSON, which already provides both days (keyed by date) plus a legend
     * mapping each pollen code to its French label, in one single fetch.
     */
    private function getPollenMessage(string $uri): string
    {
        $html = getSimpleHTMLDOM($uri);

        $scriptDom = $html->find('script[data-drupal-selector=drupal-settings-json]', 0);
        if (!$scriptDom) {
            return '';
        }
        $settings = json_decode($scriptDom->innertext, true);
        $indices = $settings['dataviz']['indicesPollen'] ?? [];
        $legend = $settings['dataviz']['pollenLegends']['indice_pollen'] ?? [];
        $taxons = $settings['dataviz']['taxonsLegends'] ?? [];

        ksort($indices);
        $dates = array_keys($indices);

        $parts = [];
        if (count($dates) >= 1) {
            $parts[] = $this->formatPollenDay('Aujourd\'hui', 'est', $indices[$dates[0]] ?? null, $legend, $taxons);
        }
        if (count($dates) >= 2) {
            $parts[] = $this->formatPollenDay('Demain', 'sera', $indices[$dates[1]] ?? null, $legend, $taxons);
        }

        return implode(' ', $parts);
    }

    private function formatPollenDay(string $when, string $verb, ?array $dayData, array $legend, array $taxons): string
    {
        $code = $dayData ? (string) $dayData['indice_pollen'] : null;
        $label = $code !== null ? ($legend[$code]['qualificatif'] ?? null) : null;

        if (!$label) {
            return "$when, le niveau de pollens est inconnu pour le moment.";
        }

        $message = "$when, le niveau de pollens $verb : $label.";

        // Detail every pollen type the site tracks (Graminées, Ambroisie,
        // Armoise, Aulne, Bouleau, Olivier...), not just the overall index.
        $taxonParts = [];
        foreach ($dayData['indices_taxons'] ?? [] as $taxon) {
            $taxonLabel = $taxons[$taxon['taxon_id']]['label'] ?? null;
            $taxonQualif = $legend[(string) $taxon['indice_pollen']]['qualificatif'] ?? null;
            if ($taxonLabel && $taxonQualif) {
                $taxonParts[] = "$taxonLabel : $taxonQualif";
            }
        }
        if ($taxonParts) {
            $message .= ' Détail par pollen : ' . implode(', ', $taxonParts) . '.';
        }

        return $message;
    }

    // The site mixes regular spaces and HTML-encoded non-breaking spaces (and
    // sometimes raw HTML entities like "&#039;") between otherwise-identical
    // sentences, which breaks naive string comparisons/duplicate detection.
    private function cleanText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES);
        $text = str_replace("\xc2\xa0", ' ', $text);
        $text = preg_replace('/ +/', ' ', $text);
        return trim($text);
    }

    /**
     * Resolves a bare city name (e.g. "bordeaux") to the "Nom/CodeInsee" path
     * segment the site actually needs, using its own commune list. Inputs that
     * already contain a slash are assumed to already be in that format (kept
     * for feeds generated before this bridge accepted plain names).
     */
    private function resolveCity(string $input): string
    {
        if ($input === '') {
            throwServerException('Merci de renseigner une ville.');
        }
        if (str_contains($input, '/')) {
            return $input;
        }

        $communes = json_decode(getContents(self::COMMUNES_URI), true);
        $target = $this->normalizeCityName($input);

        $matches = [];
        foreach ($communes['features'] ?? [] as $feature) {
            $nom = $feature['properties']['nom'] ?? '';
            $code = $feature['properties']['code'] ?? '';
            if ($code === '' || $this->normalizeCityName($nom) !== $target) {
                continue;
            }
            $matches[$code] = $nom;
        }

        if (!$matches) {
            throwServerException(
                sprintf(
                    'Aucune commune de Nouvelle-Aquitaine ne correspond à "%s". Vérifiez l\'orthographe, ou précisez le code INSEE avec le format "Nom/Code".',
                    $input
                )
            );
        }

        if (count($matches) > 1) {
            $options = [];
            foreach ($matches as $code => $nom) {
                $options[] = "$nom/$code";
            }
            throwServerException(
                sprintf(
                    'Plusieurs communes de Nouvelle-Aquitaine s\'appellent "%s": %s. Précisez avec l\'une de ces valeurs (format "Nom/Code").',
                    $input,
                    implode(', ', $options)
                )
            );
        }

        $code = array_key_first($matches);
        return $matches[$code] . '/' . $code;
    }

    private function normalizeCityName(string $name): string
    {
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name) ?: $name;
        $ascii = strtolower($ascii);
        return trim(preg_replace('/[^a-z0-9]+/', '-', $ascii), '-');
    }
}
