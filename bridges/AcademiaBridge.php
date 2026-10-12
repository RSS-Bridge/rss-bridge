<?php

declare(strict_types=1);

class AcademiaBridge extends BridgeAbstract
{
    const NAME = 'Academia';
    const URI = 'https://www.academia.edu';
    const DESCRIPTION = 'Returns papers from Academia.edu topic pages';
    const MAINTAINER = 'tillcash';
    const CACHE_TIMEOUT = 3600; // seconds (1 hour)
    const PARAMETERS = [
        [
            'topic' => [
                'name' => 'Topic name',
                'required' => true,
                'exampleValue' => 'Deadlock_Avoidance',
            ],
            'sort' => [
                'name' => 'Sort by',
                'type' => 'list',
                'values' => [
                    'Newest' => 'Newest',
                    'Top papers' => 'TopPapers',
                    'Most cited' => 'MostCited',
                    'Most downloaded' => 'MostDownloaded',
                ],
            ],
        ],
    ];

    public function getName()
    {
        $topic = $this->getInput('topic');
        if ($topic) {
            return self::NAME . ' - ' . str_replace('_', ' ', $topic);
        }

        return self::NAME;
    }

    public function collectData()
    {
        $topic = $this->getInput('topic');
        $sort = $this->getInput('sort') ?? 'Newest';

        $url = self::URI . '/Documents/in/' . $topic;
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throwServerException('Invalid topic name: ' . $topic);
        }

        if ($sort !== 'Newest') {
            $url .= '/' . $sort;
        }

        $dom = getSimpleHTMLDOM($url);

        $json = $dom->find('script[type="application/ld+json"]', 0);
        if (!$json) {
            throwServerException('Unable to parse content');
        }

        $data = Json::decode($json->innertext);

        $articles = $data['subjectOf'] ?? null;
        if (!is_array($articles) || empty($articles)) {
            throwServerException('Invalid or empty content');
        }

        $summaryByUrl = $this->extractSummaries($dom);

        foreach ($articles as $article) {
            if (($article['@type'] ?? '') !== 'ScholarlyArticle') {
                continue;
            }

            $articleUrl = $article['url'] ?? '';
            if (!filter_var($articleUrl, FILTER_VALIDATE_URL)) {
                continue;
            }

            $this->items[] = [
                'uri' => $articleUrl,
                'uid' => $articleUrl,
                'title' => $article['name'] ?? '',
                'author' => $article['author']['name'] ?? '',
                'timestamp' => $article['datePublished'] ?? '',
                'content' => $summaryByUrl[$articleUrl] ?? '',
            ];
        }
    }

    private function extractSummaries($dom): array
    {
        $summaryByUrl = [];

        foreach ($dom->find('.work-card-container') as $card) {
            $a = $card->find('.title a', 0);
            if (!$a) {
                continue;
            }

            $url = $a->href;
            $complete = $card->find('.complete.hidden', 0);
            $summary = $complete ? trim($complete->plaintext) : '';

            $summaryByUrl[$url] = $summary;
        }

        return $summaryByUrl;
    }
}
