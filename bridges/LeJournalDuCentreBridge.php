<?php

class LeJournalDuCentreBridge extends BridgeAbstract
{
    const NAME = 'Le Journal Du Centre';
    const URI = 'https://www.lejdc.fr/';
    const DESCRIPTION = 'Return latest headlines or local news for Le Journal Du Centre website (lejdc.fr).';
    const CACHE_TIMEOUT = 14400; // 4 hours
    const MAINTAINER = 'quent1';
    const PARAMETERS = [
        'global' => [
            'remove-subscribers-only-articles' => [
                'name' => 'Remove subscribers-only articles',
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
                'exampleValue' => 'nevers-58000'
            ],
        ]
    ];

    public function collectData()
    {
        // Parse parameters
        $removeSubscribersOnlyArticles = $this->getInput('remove-subscribers-only-articles') === true;
        $localitySlug = $this->getInput('locality-slug');

        // URI of already found articles (content de-duplication)
        $alreadyFoundArticlesURIs = [];

        // Fetch page HTML code
        $html = getSimpleHTMLDOM(static::URI . $localitySlug . '/');

        // Articles are detected through their titles
        foreach ($html->find('.c-titre') as $articleTitleDOMElement) {
            // Get article link
            $articleLinkDOMElement = $articleTitleDOMElement->find('a', 0);

            // Ignore articles in the « Les + partagés » block
            if (strpos($articleLinkDOMElement->id, 'les_plus_partages') !== false) {
                continue;
            }

            // Extract article URI
            $articleURI = $articleLinkDOMElement->href;

            // If the URI has already been processed, ignore it
            if (in_array($articleURI, $alreadyFoundArticlesURIs, true)) {
                continue;
            }

            // Article title
            $articleTitle = '';

            // If article is for subscribers only
            if ($articleLinkDOMElement->find('span.premium-picto', 0)) {
                // If they are filtered out
                if ($removeSubscribersOnlyArticles) {
                    continue;
                }

                // Otherwise append it to the title
                $articleTitle .= '[Abonnés] ';
            }

            // Extract first article tag (which should be the locality)
            $articleTagDOMElement = $articleTitleDOMElement->parent->find('.c-tags a.t-simple', 0);

            // Extract tag URL (locality slug)
            $articleTagURI = trim($articleTagDOMElement->href, '/');

            // If news are filted for a specific locality, filter out article for other localities
            if ($localitySlug !== '' && $localitySlug !== $articleTagURI) {
                continue;
            }

            // Otherwise, append it to the title
            $articleLocality = $articleTagDOMElement->plaintext;
            $articleTitle .= $articleLocality . ' - ';

            // If article has a keyword (its main category), append it to the title
            if ($articleKeywordDOMElement = $articleLinkDOMElement->find('span.c-motcle', 0)) {
                $articleKeyword = $articleKeywordDOMElement->plaintext;

                // If article keyword is the locality (which happens sometimes), ignore it
                if (strtolower($articleKeyword) !== strtolower($articleLocality)) {
                    $articleTitle .= $articleKeyword . ' - ';
                }
            }

            // Remove text from link children elements
            foreach ($articleLinkDOMElement->children as &$child) {
                $child->outertext = '';
            }

            // Extract title from the link
            $articleTitle .= $articleLinkDOMElement->innertext;

            // Create a new item entry
            $this->items[] = [
                'title' => $articleTitle,
                'uri' => urljoin(static::URI, $articleURI)
            ];

            // Add the URI to the list of processed articles URIs
            $alreadyFoundArticlesURIs[] = $articleURI;
        }
    }
}
