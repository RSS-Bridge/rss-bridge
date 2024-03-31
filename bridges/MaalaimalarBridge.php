<?php

class MaalaimalarBridge extends BridgeAbstract
{
    const NAME = 'Maalaimalar';
    const URI = 'https://www.maalaimalar.com/';
    const DESCRIPTION = 'Retrieve news from maalaimalar.com';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'topic' => [
                'name' => 'topic',
                'type' => 'list',
                'values' => [
                    'news' => [
                        'tamilnadu' => 'news/state',
                        'puducherry' => 'puducherry',
                        'india' => 'news/national',
                        'world' => 'news/world',
                    ],
                    'district' => [
                        'chennai' => 'chennai',
                        'ariyalur' => 'ariyalur',
                        'chengalpattu' => 'chengalpattu',
                        'coimbatore' => 'coimbatore',
                        'cuddalore' => 'cuddalore',
                        'dharmapuri' => 'dharmapuri',
                        'dindugal' => 'dindugal',
                        'erode' => 'erode',
                        'kaanchepuram' => 'kaanchepuram',
                        'kallakurichi' => 'kallakurichi',
                        'kanyakumari' => 'kanyakumari',
                        'karur' => 'karur',
                        'krishnagiri' => 'krishnagiri',
                        'madurai' => 'madurai',
                        'mayiladuthurai' => 'mayiladuthurai',
                        'nagapattinam' => 'nagapattinam',
                        'namakal' => 'namakal',
                        'nilgiris' => 'nilgiris',
                        'perambalur' => 'perambalur',
                        'pudukottai' => 'pudukottai',
                        'ramanathapuram' => 'ramanathapuram',
                        'ranipettai' => 'ranipettai',
                        'salem' => 'salem',
                        'sivagangai' => 'sivagangai',
                        'tanjore' => 'tanjore',
                        'theni' => 'theni',
                        'thenkasi' => 'thenkasi',
                        'thiruchirapalli' => 'thiruchirapalli',
                        'thirunelveli' => 'thirunelveli',
                        'thirupathur' => 'thirupathur',
                        'thiruvarur' => 'thiruvarur',
                        'thoothukudi' => 'thoothukudi',
                        'tirupur' => 'tirupur',
                        'tiruvallur' => 'tiruvallur',
                        'tiruvannamalai' => 'tiruvannamalai',
                        'vellore' => 'vellore',
                        'villupuram' => 'villupuram',
                        'virudhunagar' => 'virudhunagar',
                    ],
                    'cinema' => [
                        'news' => 'cinema/cinemanews',
                        'gossip' => 'cinema/gossip',
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
            $titleElement = $article->find('h2.title a', 0);
            if (!$titleElement) {
                continue;
            }

            $dateElement = $article->find('time.h-date span', 0);
            $date = $dateElement ? $dateElement->{'data-datestring'} . 'UTC' : '';

            $content = $this->constructContent($article);

            $this->items[] = [
                'content'   => $content,
                'timestamp' => $date,
                'title'     => $titleElement->plaintext,
                'uid'       => $titleElement->href,
                'uri'       => self::URI . $titleElement->href,
            ];
        }
    }

    private function constructContent($article)
    {
        $content = '';
        $imageElement = $article->find('div.ignore-autoplay img', 0);
        if ($imageElement) {
            $content .= '<p><img src="' . $imageElement->{'data-src'} . '"></p>';
        }

        $storyElement = $article->find('div.story-content', 0);
        if ($storyElement) {
            $content .= $storyElement->innertext;
        }

        return $content;
    }
}
