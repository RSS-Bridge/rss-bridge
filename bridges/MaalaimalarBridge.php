<?php

class MaalaimalarBridge extends BridgeAbstract
{
    const NAME = 'Maalaimalar';
    const URI = 'https://www.maalaimalar.com';
    const DESCRIPTION = 'Retrieve news from maalaimalar.com';
    const CACHE_TIMEOUT = 60 * 5; // 5 minutes
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'topic' => [
                'name' => 'topic',
                'type' => 'list',
                'values' => [
                    'news' => [
                        'tamilnadu' => '/news/tamilnadu',
                        'puducherry' => '/news/puducherry',
                        'india' => '/news/national',
                        'world' => '/news/world',
                    ],
                    'district' => [
                        'chennai' => '/news/district/chennai',
                        'ariyalur' => '/news/district/ariyalur',
                        'chengalpattu' => '/news/district/chengalpattu',
                        'coimbatore' => '/news/district/coimbatore',
                        'cuddalore' => '/news/district/cuddalore',
                        'dharmapuri' => '/news/district/dharmapuri',
                        'dindugal' => '/news/district/dindugal',
                        'erode' => '/news/district/erode',
                        'kaanchepuram' => '/news/district/kaanchepuram',
                        'kallakurichi' => '/news/district/kallakurichi',
                        'kanyakumari' => '/news/district/kanyakumari',
                        'karur' => '/news/district/karur',
                        'krishnagiri' => '/news/district/krishnagiri',
                        'madurai' => '/news/district/madurai',
                        'mayiladuthurai' => '/news/district/mayiladuthurai',
                        'nagapattinam' => '/news/district/nagapattinam',
                        'namakal' => '/news/district/namakal',
                        'nilgiris' => '/news/district/nilgiris',
                        'perambalur' => '/news/district/perambalur',
                        'pudukottai' => '/news/district/pudukottai',
                        'ramanathapuram' => '/news/district/ramanathapuram',
                        'ranipettai' => '/news/district/ranipettai',
                        'salem' => '/news/district/salem',
                        'sivagangai' => '/news/district/sivagangai',
                        'tanjore' => '/news/district/tanjore',
                        'theni' => '/news/district/theni',
                        'thenkasi' => '/news/district/thenkasi',
                        'thiruchirapalli' => '/news/district/thiruchirapalli',
                        'thirunelveli' => '/news/district/thirunelveli',
                        'thirupathur' => '/news/district/thirupathur',
                        'thiruvarur' => '/news/district/thiruvarur',
                        'thoothukudi' => '/news/district/thoothukudi',
                        'tirupur' => '/news/district/tirupur',
                        'tiruvallur' => '/news/district/tiruvallur',
                        'tiruvannamalai' => '/news/district/tiruvannamalai',
                        'vellore' => '/news/district/vellore',
                        'villupuram' => '/news/district/villupuram',
                        'virudhunagar' => '/news/district/virudhunagar',
                    ],
                    'cinema' => [
                        'news' => '/cinema/cinemanews',
                        'gossip' => '/cinema/gossip',
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
        $dom = getSimpleHTMLDOM(self::URI . $this->getInput('topic'));
        $articles = $dom->find('div.mb-20.infinite-card-wrapper.white-section');

        foreach ($articles as $article) {
            $title = $article->find('h2.title a', 0);
            if (!$title) {
                continue;
            }

            $location = $article->find('a.location-anchor', 0)->href ?? '';
            $segments = array_filter(explode('/', $location));
            $category = [end($segments)];

            $date = $article->find('time.h-date span', 0);
            $timestamp = $date ? $date->{'data-datestring'} . 'UTC' : '';

            $content = $this->constructContent($article);

            $this->items[] = [
                'categories' => $category,
                'content'    => $content,
                'timestamp'  => $timestamp,
                'title'      => $title->plaintext,
                'uid'        => $title->href,
                'uri'        => self::URI . $title->href,
            ];
        }
    }

    private function constructContent($article)
    {
        $content = '';
        $image = $article->find('div.ignore-autoplay img', 0);

        if ($image && isset($image->{'data-src'})) {
            $imageUrl = str_replace('500x300_', '', $image->{'data-src'});

            if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $content = sprintf('<p><img src="%s"></p>', htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'));
            }
        }

        $story = $article->find('div.story-content', 0);
        if ($story) {
            $content .= $story->innertext;
        }

        return $content;
    }
}
