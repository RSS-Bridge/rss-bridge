<?php

class ZonebourseBridge extends BridgeAbstract
{
    const NAME = 'Zonebourse';
    const URI = 'https://www.zonebourse.com';
    const DESCRIPTION = 'Retrieve news from zonebourse.com';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'topic' => [
                'name' => 'topic',
                'type' => 'list',
                'values' => [
                    'toute-l-actualite' => [
                        'monde' => '/actualite-bourse/',
                        'france' => '/actualite-bourse/regions/locales/',
                        'europe' => '/actualite-bourse/regions/europe/',
                        'amerique-du-nord' => '/actualite-bourse/regions/amerique-du-nord/',
                        'amerique-du-sud' => '/actualite-bourse/regions/amerique-du-sud/',
                        'asie' => '/actualite-bourse/regions/asie/',
                        'afrique' => '/actualite-bourse/regions/afrique/',
                        'moyen-orient' => '/actualite-bourse/regions/moyenorient/',
                        'emergents' => '/actualite-bourse/regions/emergents/',
                    ],
                    'societes' => [
                        'toute-l-actualite' => '/actualite-bourse/societes/',
                        'reco-analystes' => '/actualite-bourse/societes/recommandations/',
                        'rumeurs' => '/actualite-bourse/societes/rumeur/',
                        'introductions' => '/actualite-bourse/societes/introductions/',
                        'operations-capitalistiques' => '/actualite-bourse/societes/operations/',
                        'nouveaux-contrats' => '/actualite-bourse/societes/nouveaux-contrats/',
                        'profits-warnings' => '/actualite-bourse/societes/profits-warnings/',
                        'nominations' => '/actualite-bourse/societes/nominations/',
                        'communiques' => '/actualite-bourse/societes/communique/',
                        'operations-sur-titre' => '/actualite-bourse/societes/operations_titre/',
                        'publications-de-resultats' => '/actualite-bourse/societes/publications/',
                        'nouveaux-marches' => '/actualite-bourse/societes/nouveaux-marches/',
                        'nouveaux-produits' => '/actualite-bourse/societes/nouveaux-produits/',
                        'strategies-societes' => '/actualite-bourse/societes/strategies-societes/',
                        'risques-juridiques' => '/actualite-bourse/societes/risques-juridiques/',
                        'rachats-d-actions' => '/actualite-bourse/societes/rachats-actions/',
                        'fusions-et-acquisitions' => '/actualite-bourse/societes/fusions-acquisitions/',
                        'call-transcripts' => '/actualite-bourse/societes/call-transcripts/',
                        'guidance' => '/actualite-bourse/societes/guidance/',
                    ],
                ],
            ],
        ],
    ];

    public function getName()
    {
        $topic = $this->getKey('topic');
        return self::NAME . ($topic ? ': ' . $topic : '');
    }

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(self::URI . $this->getInput('topic'));
        $articles = $dom->find('table#newsScreener tbody tr');

        if (!$articles) {
            returnServerError('Failed to retrieve news content');
        }

        foreach ($articles as $article) {
            $element = $article->find('.grid a', 0);

            if (!$element || empty($element->plaintext) || empty($element->href)) {
                continue;
            }

            $date = $article->find('span.js-date-relative.txt-muted.h-100', 0);
            $timestamp = $date->{'data-utc-date'} ?? '';

            $this->items[] = [
                'timestamp'  => $timestamp,
                'title'      => trim($element->plaintext),
                'uid'        => $element->href,
                'uri'        => self::URI . $element->href,
            ];
        }
    }
}
