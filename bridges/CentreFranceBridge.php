<?php

class CentreFranceBridge extends BridgeAbstract
{
    const NAME = 'Centre France Newspapers';
    const URI = 'https://www.centrefrance.com/';
    const DESCRIPTION = 'Common bridge for all Centre France group newspapers.';
    const CACHE_TIMEOUT = 7200;
    const MAINTAINER = 'quent1';
    const PARAMETERS = [
        'global' => [
            'newspaper' => [
                'name' => 'Newspaper',
                'type' => 'list',
                'required' => true,
                'values' => [
                    'La Montagne' => 'lamontagne.fr',
                    'Le Populaire du Centre' => 'lepopulaire.fr',
                    'La République du Centre' => 'larep.fr',
                    'Le Berry Républicain' => 'leberry.fr',
                    'L\'Yonne Républicaine' => 'lyonne.fr',
                    'L\'Écho Républicain' => 'lechorepublicain.fr',
                    'Le Journal du Centre' => 'lejdc.fr',
                    'L\'Éveil de la Haute-Loire' => 'leveil.fr',
                    'Le Pays' => 'le-pays.fr'
                ]
            ],
            'remove-reserved-for-subscribers-articles' => [
                'name' => 'Remove reserved for subscribers articles',
                'type' => 'checkbox',
                'title' => 'Filter out articles that are only available to subscribers'
            ]
        ],
        'Local news' => [
            'locality-slug' => [
                'name' => 'Locality slug',
                'type' => 'text',
                'required' => false,
                'title' => 'Fetch articles for a specific locality. If not set, headlines from the front page will be used instead.',
                'exampleValue' => 'moulins-03000'
            ],
        ]
    ];

    public function detectParameters($url)
    {
        $regex = '/^(https?:\/\/)?(www\.)?([a-z-]+\.fr)(\/)?([a-z-]+-[0-9]{5})?(\/)?$/';
        $url = strtolower($url);

        if (preg_match($regex, $url, $urlMatches) === 0) {
            return null;
        }

        if (!in_array($urlMatches[3], self::PARAMETERS['global']['newspaper']['values'], true)) {
            return null;
        }

        return [
            'newspaper' => $urlMatches[3],
            'locality-slug' => empty($urlMatches[5]) ? null : $urlMatches[5]
        ];
    }

    public function collectData()
    {
        $localitySlug = $this->getInput('locality-slug') ?? '';
        $alreadyFoundArticlesURIs = [];

        $html = getSimpleHTMLDOM('https://www.' . $this->getInput('newspaper') . '/' . $localitySlug . '/');

        // Articles are detected through their titles
        foreach ($html->find('.c-titre') as $articleTitleDOMElement) {
            $articleLinkDOMElement = $articleTitleDOMElement->find('a', 0);

            // Ignore articles in the « Les + partagés » block
            if (strpos($articleLinkDOMElement->id, 'les_plus_partages') !== false) {
                continue;
            }

            $articleURI = $articleLinkDOMElement->href;

            // If the URI has already been processed, ignore it
            if (in_array($articleURI, $alreadyFoundArticlesURIs, true)) {
                continue;
            }

            // If news are filtered for a specific locality, filter out article for other localities
            if ($localitySlug !== '' && !str_contains($articleURI, $localitySlug)) {
                continue;
            }

            $articleTitle = '';

            // If article is reserved for subscribers
            if ($articleLinkDOMElement->find('span.premium-picto', 0)) {
                if ($this->getInput('remove-reserved-for-subscribers-articles') === true) {
                    continue;
                }

                $articleTitle .= '🔒 ';
            }

            $articleTitleDOMElement = $articleLinkDOMElement->find('span[data-tb-title]', 0);
            if ($articleTitleDOMElement === null) {
                continue;
            }

            $articleTitle .= $articleLinkDOMElement->find('span[data-tb-title]', 0)->innertext;

            $this->items[] = [
                'title' => $articleTitle,
                'uri' => urljoin('https://www.' . $this->getInput('newspaper') . '/', $articleURI)
            ];

            $alreadyFoundArticlesURIs[] = $articleURI;
        }
    }
}
