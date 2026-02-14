<?php

class CdactionBridge extends BridgeAbstract
{
    const NAME = 'CD-ACTION';
    const URI = 'https://cdaction.pl';
    const DESCRIPTION = 'Fetches the latest posts from given category.';
    const MAINTAINER = 'tomaszkane';
    const PARAMETERS = [ [
        'category' => [
            'name' => 'Kategoria',
            'type' => 'list',
            'values' => [
                'Najnowsze (wszystkie)' => 'najnowsze',
                'Newsy' => 'newsy',
                'Recenzje' => 'recenzje',
                'Teksty' => [
                    'Publicystyka' => 'teksty',
                    'Zapowiedzi' => 'zapowiedzi',
                    'Już graliśmy' => 'juz-gralismy',
                    'Retro' => 'retro',
                ],
                'Kultura' => 'kultura',
                'Technologie' => [
                    'Artykuły' => 'artykuly',
                    'Technologie' => 'technologie',
                    'Testy' => 'testy',
                ],
                'Na luzie' => [
                    'Nadgodziny' => 'nadgodziny',
                ]
            ]
        ]]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI() . '/' . $this->getInput('category'));

        $mainArticles = $html->find('ul.news-list .first-item');
        $this->processArticles($mainArticles);
        $articles = $html->find('.news-list li.article');
        $this->processArticles($articles);
    }

    private function processArticles(array $articles): void
    {
        /** @var simple_html_dom_node $article */
        foreach ($articles as $article) {
            $item = [];
            $item['uri'] = $article->find('a.article-link', 0)->getAttribute('href');
            $item['title'] = $article->find('h3 .title-desktop', 0)->innertext;
            $item['uid'] = $item['uri'];

            $this->items[] = $item;
        }
    }
}
