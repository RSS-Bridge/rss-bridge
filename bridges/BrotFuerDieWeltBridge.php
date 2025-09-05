<?php

declare(strict_types=1);

class BrotFuerDieWeltBridge extends BridgeAbstract
{
    const NAME = 'Brot für die Welt';
    const URI = 'https://www.brot-fuer-die-welt.de';
    const DESCRIPTION = 'Listet die letzten Blogeinträge bzw. Pressemitteilungen von Brot für die Welt.';
    const MAINTAINER = 'lymnyx';
    const PARAMETERS = [[
        'newsType' => [
            'name' => 'Neuigkeitentyp',
            'type' => 'list',
            'values' => [
                'Blog' => 'blog',
                'Pressemitteilungen' => 'press',
            ],
            'defaultValue' => 'blog',
        ],
    ]];
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $newsType = $this->getInput('newsType');
        $pageURI = 'https://www.brot-fuer-die-welt.de/blog/alle-beitraege/';
        if ($newsType == 'press') {
            $pageURI = 'https://www.brot-fuer-die-welt.de/presse/alle-pressemitteilungen/';
        };
        $maxArticles = 100;
        $html = getSimpleHTMLDOMCached($pageURI, 3600);

        $articles = $html->find('body div.news div.news-list-view div.article')
            or throwServerException('Could not find articles for: ' . $pageURI);

        $articles = array_slice($articles, 0, $maxArticles);

        if ($newsType == 'blog') {
            foreach ($articles as $article) {
                $item = [];

                $category = $article->find('div.news-img-wrap div.teaser-badge', 0)->plaintext;

                if ($category) {
                    $category = ' (' . trim($category) . ')';
                };

                $item['title'] = $article->find('h3.headline', 0)->plaintext . $category;

                $newsDateAuthor = $article->find('span.news-list-date', 0)->plaintext;

                if ($newsDateAuthor) {
                    $splitDateAuthor = explode(' | ', $newsDateAuthor);

                    $item['timestamp'] = $splitDateAuthor[0];

                    if (count($splitDateAuthor) > 1) {
                        $item['author'] = $splitDateAuthor[1];
                    }
                }

                $item['uri'] = urljoin('https://www.brot-fuer-die-welt.de', $article->find('div.teaser-text a.more-link', 0)->href);

                $articleHTML = getSimpleHTMLDOMCached($item['uri'], 86400);
                $description = $articleHTML->find('body div.intro-box p', 0);

                if (!$description) {
                    $description = $article->find('div.teaser-text div p', 0)->plaintext;
                };

                $item['content'] = $description;

                $item['enclosures'] = [
                    urljoin('https://www.brot-fuer-die-welt.de', $article->find('div.news-img-wrap picture img', 0)->src),
                ];

                $this->items[] = $item;
            }
        } else {
            foreach (array_values($articles) as $i => $article) {
                $item = [];

                $item['title'] = $article->find('div.header h3 span', 0)->plaintext;
                $item['timestamp'] = $article->find('div.footer span.news-list-date time', 0)->plaintext;
                $item['author'] = 'Brot für die Welt (Evangelisches Werk für Diakonie und Entwicklung e.V.)';
                $item['uri'] = urljoin('https://www.brot-fuer-die-welt.de', $article->find('div.teaser-text a.more-link', 0)->href);

                $miniDescription = $article->find('div.teaser-text div p', 0)->plaintext;

                if ($i > 19) {
                    $description = $miniDescription . '<br><br>Weiterlesen auf <a href="' . $item['uri'] . '">brot-fuer-die-welt.de</a>';
                } else {
                    $articleHTML = getSimpleHTMLDOMCached($item['uri'], 86400);
                    $description = $articleHTML->find('body article.article-section div.news-text-wrap', 0);

                    if (!$description) {
                        $description = $article->find('div.teaser-text div p', 0)->plaintext;
                    };
                };
                $item['content'] = $description;

                $this->items[] = $item;
            }
        }
    }
}

