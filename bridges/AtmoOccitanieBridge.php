<?php

declare(strict_types=1);

class AtmoOccitanieBridge extends BridgeAbstract
{
    const NAME = 'Atmo Occitanie';
    const URI = 'https://www.atmo-occitanie.org/';
    const DESCRIPTION = 'Fetches the latest air polution of cities in Occitanie from Atmo';
    const MAINTAINER = 'floviolleau';
    const PARAMETERS = [[
        'city' => [
            'name' => 'Ville',
            'required' => true,
            'exampleValue'  => 'cahors'
        ]
    ]];
    const CACHE_TIMEOUT = 7200; // 2h

    public function collectData()
    {
        $uri = self::URI . $this->getInput('city');

        $html = getSimpleHTMLDOM($uri);

        $generalMessage = $this->cleanText($html->find('.landing-ville .city-banner .iqa-avertissement', 0)->innertext);
        $recommendationsDom = $html->find('.recommandations', 0);
        if (!$recommendationsDom) {
            throwServerException('Impossible de trouver les recommandations pour cette ville. Le site a probablement changé de structure.');
        }
        $recommendationsItemDom = $recommendationsDom->find('.recommandation-item .label');

        $recommendationsMessage = '';

        $i = 0;
        $len = count($recommendationsItemDom);
        foreach ($recommendationsItemDom as $key => $value) {
            if ($i == 0) {
                $recommendationsMessage .= trim($value->innertext) . '.';
            } else {
                $recommendationsMessage .= ' ' . trim($value->innertext) . '.';
            }
            $i++;
        }

        $lastRecommendationsDom = $recommendationsDom->find('.col-md-6', -1);
        $informationHeaderMessage = $lastRecommendationsDom->find('.heading', 0)->innertext;
        $indice = $lastRecommendationsDom->find('.current-indice .indice div', 0)->innertext;
        $informationDescriptionMessage = $this->cleanText(
            $lastRecommendationsDom->find('.current-indice .description p', 0)->innertext
        );

        // The page repeats the banner's headline sentence at the start of the
        // detailed description; drop it from there so it isn't said twice.
        if ($generalMessage !== '' && str_starts_with($informationDescriptionMessage, $generalMessage)) {
            $informationDescriptionMessage = trim(substr($informationDescriptionMessage, strlen($generalMessage)));
        }

        $message = "$generalMessage L'indice est de " . (6 - $indice) . "/6. $informationDescriptionMessage. $recommendationsMessage";
        $message .= ' ' . $this->getForecastMessage($html);
        $city = $this->getInput('city');

        $item['uri'] = $uri;
        $today = date('d/m/Y');
        $item['title'] = "Bulletin de l'air du $today pour la ville : $city.";
        $item['title'] .= ' Retrouvez plus d\'informations en allant sur atmo-occitanie.org #QualiteAir. ' . $message;
        $item['author'] = self::MAINTAINER;
        $item['content'] = $message;
        $item['uid'] = hash('sha256', $item['title']);

        $this->items[] = $item;
    }

    /**
     * Builds the "tomorrow's air quality" + "today's and tomorrow's pollen risk"
     * part of the message. The page only renders prose for today's air quality
     * (handled above); everything else here is derived from the day-by-day
     * forecast codes embedded in the page's own settings JSON (used to feed its
     * forecast charts), matched against the indice/pollen legends also present
     * on the page so the wording follows whatever the site currently uses.
     */
    private function getForecastMessage(simple_html_dom $html): string
    {
        $scriptDom = $html->find('script[data-drupal-selector=drupal-settings-json]', 0);
        if (!$scriptDom) {
            return '';
        }
        $settings = json_decode($scriptDom->innertext, true);
        $cityIqa = $settings['atmo_mesures']['city_iqa'] ?? [];
        $cityPollen = $settings['atmo_mesures']['city_pollen'] ?? [];

        // Both arrays are a rolling window of daily codes ending on tomorrow's
        // forecast: last entry = tomorrow, the one before it = today.
        usort($cityIqa, fn($a, $b) => $a['date'] <=> $b['date']);
        usort($cityPollen, fn($a, $b) => $a['date'] <=> $b['date']);

        $airLegend = $this->readIndiceLegend($html, "Qualité de l'air");
        $pollenLegend = $this->readIndiceLegend($html, 'Prévision Pollens');

        $parts = [];

        if (count($cityIqa) >= 2) {
            $tomorrow = $cityIqa[count($cityIqa) - 1];
            $label = $airLegend[(string) $tomorrow['iqa']] ?? null;
            $parts[] = $label ? "Demain, l'indice de qualité de l'air sera de {$tomorrow['iqa']}/6 ($label)." : "Demain, l'indice de qualité de l'air est inconnu pour le moment.";
        }

        $todayPollenDom = $html->find('#resume-pollen .pollen-avertissement', 0);
        if ($todayPollenDom) {
            $parts[] = $this->cleanText($todayPollenDom->innertext);
        } elseif ($cityPollen) {
            $todayIndex = count($cityPollen) >= 2 ? count($cityPollen) - 2 : count($cityPollen) - 1;
            $label = $pollenLegend[(string) $cityPollen[$todayIndex]['pollen']] ?? null;
            $parts[] = $label ? "Aujourd'hui, le niveau de pollens est : $label." : "Aujourd'hui, le niveau de pollens est inconnu pour le moment.";
        }

        if (count($cityPollen) >= 2) {
            $tomorrow = $cityPollen[count($cityPollen) - 1];
            $label = $pollenLegend[(string) $tomorrow['pollen']] ?? null;
            $parts[] = $label ? "Demain, le niveau de pollens sera : $label." : 'Demain, le niveau de pollens est inconnu pour le moment.';
        }

        return implode(' ', $parts);
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

    // Reads a "code => label" map from one of the page's own legends (e.g. "1" =>
    // "Bon"), identified by its visible title, instead of hard-coding labels that
    // could drift from what the site displays.
    private function readIndiceLegend(simple_html_dom $html, string $title): array
    {
        $legend = [];
        foreach ($html->find('.indice-container') as $container) {
            $titleDom = $container->find('.title', 0);
            if (!$titleDom || trim($titleDom->plaintext) !== $title) {
                continue;
            }
            foreach ($container->find('.indice-item') as $item) {
                $codeDom = $item->find('span', 0);
                $labelDom = $item->find('.label', 0);
                if ($codeDom && $labelDom) {
                    $legend[trim($codeDom->plaintext)] = trim($labelDom->plaintext);
                }
            }
            break;
        }
        return $legend;
    }
}
