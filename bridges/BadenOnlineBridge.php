<?php

declare(strict_types=1);

class BadenOnlineBridge extends BridgeAbstract
{
    public const NAME = 'Baden Online';
    public const URI = 'https://bo.de';
    public const DESCRIPTION = 'Retrieve news from Baden Online';
    public const MAINTAINER = 'tillcash';
    public const CACHE_TIMEOUT = 60 * 5; // seconds (5 minutes)
    public const PARAMETERS = [
        [
            'tag' => [
                'name' => 'tag',
                'type' => 'list',
                'values' => [
                    'ortenau' => [
                        'themen-des-tages' => 'thema-des-tages',
                        'offenburg' => 'offenburg',
                        'lahr' => 'lahr',
                        'achern' => 'achern',
                        'oberkirch' => 'oberkirch',
                        'kinzigtal' => 'kinzigtal',
                        'kehl' => 'kehl',
                    ],
                    'aus-der-welt' => [
                        'aus-der-welt' => 'aus-der-welt',
                        'politik' => 'politik',
                        'wirtschaft' => 'wirtschaft',
                        'bawue' => 'baden-wurttemberg',
                        'kultur' => 'kultur',
                    ],
                    'regiosport' => [
                        'regiosport' => 'regiosport',
                        'fussball' => 'fussball',
                        'handball' => 'handball',
                        'mehr-sport' => 'mehr-sport',
                    ],
                    'marktplatz' => [
                        'marktplatz' => 'anzeige',
                        'ausschreibung' => 'ausschreibung',
                        // 'advertorials' => 'https://advertorials.bo.de',
                        // 'trauer' => 'https://trauer-ortenau.de',
                        // 'jobs' => 'https://jobs.bo.de',
                        // 'kleinanzeigen' => 'https://kleinanzeigen.bo.de',
                    ],
                    'blaulicht' => [
                        'blaulicht' => 'blaulicht',
                    ],
                ],
                'defaultValue' => 'achern',
            ],
        ],
    ];

    public function getName()
    {
        $tag = $this->getInput('tag');
        return self::NAME . ($tag ? ' - ' . ucfirst($tag) : '');
    }

    public function collectData()
    {
        $tag = $this->getInput('tag');
        $url = urljoin(self::URI, ($tag === 'mehr-sport') ? $tag : 'tag/' . $tag);

        $dom = getSimpleHTMLDOM($url);

        $articles = $dom->find('article');
        if (!$articles) {
            throwServerException('Invalid or empty content');
        }

        foreach ($articles as $article) {
            $a = $article->find('a', 0);
            if (!$a || !($href = $a->getAttribute('href'))) {
                continue;
            }

            $articleImageUrl = '';
            $img = $article->find('img', 0);
            if ($img && ($src = $img->getAttribute('src'))) {
                $articleImageUrl = urljoin(self::URI, $src);
            }

            $this->items[] = [
                'uri' => urljoin(self::URI, $href),
                'content' => trim($article->find('.post-excerpt', 0)->plaintext ?? ''),
                'title' => trim($article->find('.post-headline', 0)->plaintext ?? ''),
                'enclosures' => $articleImageUrl ? [$articleImageUrl] : [],
            ];
        }
    }
}
