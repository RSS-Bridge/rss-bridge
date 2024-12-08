<?php

class DailythanthiBridge extends BridgeAbstract
{
    const NAME = 'Dailythanthi';
    const URI = 'https://www.dailythanthi.com';
    const DESCRIPTION = 'Retrieve news from dailythanthi.com';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'topic' => [
                'name' => 'topic',
                'type' => 'list',
                'values' => [
                    'news' => [
                        'tamilnadu' => 'news/state',
                        'india' => 'news/india',
                        'world' => 'news/world',
                        'sirappu-katturaigal' => 'news/sirappukatturaigal',
                    ],
                    'cinema' => [
                        'news' => 'cinema/cinemanews',
                    ],
                    'sports' => [
                        'sports' => 'sports',
                        'cricket' => 'sports/cricket',
                        'football' => 'sports/football',
                        'tennis' => 'sports/tennis',
                        'hockey' => 'sports/hockey',
                        'other-sports' => 'sports/othersports',
                    ],
                    'devotional' => [
                        'devotional' => 'others/devotional',
                        'aalaya-varalaru' => 'aalaya-varalaru',
                    ],
                ],
            ],
        ],
    ];

    public function getName()
    {
        $topic = $this->getKey('topic');
        return self::NAME . ($topic ? ' - ' . ucfirst($topic) : '');
    }

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(self::URI . '/' . $this->getInput('topic'));

        foreach ($dom->find('div.ListingNewsWithMEDImage') as $element) {
            $slug = $element->find('a', 1);
            $title = $element->find('h3', 0);
            if (!$slug || !$title) {
                continue;
            }

            $url = self::URI . $slug->href;
            $date = $element->find('span', 1);
            $date = $date ? $date->{'data-datestring'} : '';

            $this->items[] = [
                'content'   => $this->constructContent($url),
                'timestamp' => $date ? $date . 'UTC' : '',
                'title'     => $title->plaintext,
                'uid'       => $slug->href,
                'uri'       => $url,
            ];
        }
    }

    private function constructContent($url)
    {
        $dom = getSimpleHTMLDOMCached($url);

        $article = $dom->find('div.details-content-story', 0);
        if (!$article) {
            return 'Content Not Found';
        }

        // Remove ads
        foreach ($article->find('div[id*="_ad"]') as $remove) {
            $remove->outertext = '';
        }

        // Correct image tag in $article
        foreach ($article->find('h-img') as $img) {
            $img->parent->outertext = sprintf('<p><img src="%s"></p>', $img->src);
        }

        $image = $dom->find('div.main-image-caption-container img', 0);
        $image = $image ? '<p>' . $image->outertext . '</p>' : '';

        return $image . $article;
    }
}
