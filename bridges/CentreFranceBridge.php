<?php

class CentreFranceBridge extends BridgeAbstract
{
    const NAME = 'Centre France Newspapers';
    const URI = 'https://www.centrefrance.com/';
    const DESCRIPTION = 'Common bridge for all Centre France group newspapers.';
    const CACHE_TIMEOUT = 7200; // 2h
    const MAINTAINER = 'quent1';
    const PARAMETERS = [
        'global' => [
            'newspaper' => [
                'name' => 'Newspaper',
                'type' => 'list',
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
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'title' => 'How many articles to fetch. 0 to disable.',
                'required' => true,
                'defaultValue' => 15
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

    public function collectData()
    {
        $value = $this->getInput('limit');
        if (is_numeric($value) && (int)$value >= 0) {
            $limit = $value;
        } else {
            $limit = static::PARAMETERS['global']['limit']['defaultValue'];
        }

        if (empty($this->getInput('newspaper'))) {
            return;
        }

        $localitySlug = $this->getInput('locality-slug') ?? '';
        $alreadyFoundArticlesURIs = [];

        $newspaperUrl = 'https://www.' . $this->getInput('newspaper') . '/' . $localitySlug . '/';
        $html = getSimpleHTMLDOM($newspaperUrl);

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

            if ($limit > 0 && count($this->items) === $limit) {
                break;
            }

            $articleTitle .= $articleLinkDOMElement->find('span[data-tb-title]', 0)->innertext;
            $articleFullURI = urljoin('https://www.' . $this->getInput('newspaper') . '/', $articleURI);

            $item = [
                'title' => $articleTitle,
                'uri' => $articleFullURI,
                ...$this->collectArticleData($articleFullURI)
            ];
            $this->items[] = $item;

            $alreadyFoundArticlesURIs[] = $articleURI;
        }
    }

    private function collectArticleData($uri): array
    {
        $html = getSimpleHTMLDOMCached($uri, 86400 * 90); // 90d

        $item = [
            'enclosures' => [],
        ];

        $articleInformations = $html->find('.c-article-informations p');
        if (is_array($articleInformations) && $articleInformations !== []) {
            $authorPosition = 1;

            // Article publication date
            if (preg_match('/(\d{2})\/(\d{2})\/(\d{4})( à (\d{2})h(\d{2}))?/', $articleInformations[0]->innertext, $articleDateParts) > 0) {
                $articleDate = new \DateTime('midnight');
                $articleDate->setDate($articleDateParts[3], $articleDateParts[2], $articleDateParts[1]);

                if (count($articleDateParts) === 7) {
                    $articleDate->setTime($articleDateParts[5], $articleDateParts[6]);
                }

                $item['timestamp'] = $articleDate->getTimestamp();
            }

            // Article update date
            if (count($articleInformations) >= 2 && preg_match('/(\d{2})\/(\d{2})\/(\d{4})( à (\d{2})h(\d{2}))?/', $articleInformations[1]->innertext, $articleDateParts) > 0) {
                $authorPosition = 2;

                $articleDate = new \DateTime('midnight');
                $articleDate->setDate($articleDateParts[3], $articleDateParts[2], $articleDateParts[1]);

                if (count($articleDateParts) === 7) {
                    $articleDate->setTime($articleDateParts[5], $articleDateParts[6]);
                }

                $item['timestamp'] = $articleDate->getTimestamp();
            }

            if (count($articleInformations) === ($authorPosition + 1)) {
                $item['author'] = $articleInformations[$authorPosition]->innertext;
            }
        }

        $articleContent = $html->find('.b-article .contenu > *');
        if (is_array($articleContent)) {
            $item['content'] = '';

            foreach ($articleContent as $contentPart) {
                if (in_array($contentPart->getAttribute('id'), ['cf-audio-player', 'poool-widget'], true)) {
                    continue;
                }

                $articleHiddenParts = $contentPart->find('.bloc, .p402_hide');
                if (is_array($articleHiddenParts)) {
                    foreach ($articleHiddenParts as $articleHiddenPart) {
                        $contentPart->removeChild($articleHiddenPart);
                    }
                }

                $item['content'] .= $contentPart->innertext;
            }
        }

        $articleIllustration  = $html->find('.photo-wrapper .photo-box img');
        if (is_array($articleIllustration) && count($articleIllustration) === 1) {
            $item['enclosures'][] = $articleIllustration[0]->getAttribute('src');
        }

        $articleAudio = $html->find('#cf-audio-player-container audio');
        if (is_array($articleAudio) && count($articleAudio) === 1) {
            $item['enclosures'][] = $articleAudio[0]->getAttribute('src');
        }

        $articleTags = $html->find('.b-article > ul.c-tags > li > a.t-simple');
        if (is_array($articleTags)) {
            $item['categories'] = array_map(static fn ($articleTag) => $articleTag->innertext, $articleTags);
        }

        $explode = explode('_', $uri);
        $array_reverse = array_reverse($explode);
        $string = $array_reverse[0];
        $uid = rtrim($string, '/');
        if (is_numeric($uid)) {
            $item['uid'] = $uid;
        }

        // If the article is a "grand format", we use another parsing strategy
        if ($item['content'] === '' && $html->find('article') !== []) {
            $articleContent = $html->find('article > section');
            foreach ($articleContent as $contentPart) {
                if ($contentPart->find('#journo') !== []) {
                    $item['author'] = $contentPart->find('#journo')->innertext;
                    continue;
                }

                $item['content'] .= $contentPart->innertext;
            }
        }

        $item['content'] = str_replace('<span class="p-premium">premium</span>', '🔒', $item['content']);
        $item['content'] = trim($item['content']);

        return $item;
    }

    public function getName()
    {
        if (empty($this->getInput('newspaper'))) {
            return static::NAME;
        }

        $newspaperNameByDomain = array_flip(self::PARAMETERS['global']['newspaper']['values']);
        if (!isset($newspaperNameByDomain[$this->getInput('newspaper')])) {
            return static::NAME;
        }

        $completeTitle = $newspaperNameByDomain[$this->getInput('newspaper')];

        if (!empty($this->getInput('locality-slug'))) {
            $localityName = explode('-', $this->getInput('locality-slug'));
            array_pop($localityName);
            $completeTitle .= ' ' . ucfirst(implode('-', $localityName));
        }

        return $completeTitle;
    }

    public function getIcon()
    {
        if (empty($this->getInput('newspaper'))) {
            return static::URI . '/favicon.ico';
        }

        return 'https://www.' . $this->getInput('newspaper') . '/favicon.ico';
    }

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
}
