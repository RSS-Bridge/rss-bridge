<?php

class ElsevierBridge extends BridgeAbstract
{
    const MAINTAINER = 'dvikan';
    const NAME = 'Elsevier journals recent articles';
    const URI = 'https://www.journals.elsevier.com/';
    const CACHE_TIMEOUT = 43200; //12h
    const DESCRIPTION = 'Returns the recent articles published in Elsevier journals';

    const PARAMETERS = [ [
        'j' => [
            'name' => 'Journal name',
            'required' => true,
            'exampleValue' => 'academic-pediatrics',
            'title' => 'Insert html-part of your journal'
        ]
    ]];

    public function collectData()
    {
        // Not all journals have the /recent-articles page
        $url = sprintf('https://www.journals.elsevier.com/%s/recent-articles/', $this->getInput('j'));
        $html = getSimpleHTMLDOM($url);

        foreach ($html->find('article') as $recentArticle) {
            $item = [];
            $item['uri'] = $recentArticle->find('a', 0)->getAttribute('href');
            $item['title'] = $recentArticle->find('h2', 0)->plaintext;
            $item['author'] = $recentArticle->find('p > span', 0)->plaintext;
            $publicationDateString = trim($recentArticle->find('p > span', 1)->plaintext);
            $publicationDate = DateTimeImmutable::createFromFormat('F d, Y', $publicationDateString);
            if ($publicationDate) {
                $item['timestamp'] = $publicationDate->getTimestamp();
            }
            $this->items[] = $item;
        }
    }

    public function getIcon(): string
    {
        return 'https://cdn.elsevier.io/verona/includes/favicons/favicon-32x32.png';
    }
}
