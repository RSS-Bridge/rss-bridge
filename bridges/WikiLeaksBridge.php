<?php

class WikiLeaksBridge extends BridgeAbstract
{
    const NAME = 'WikiLeaks';
    const URI = 'https://wikileaks.org';
    const DESCRIPTION = 'Returns the latest news or articles from WikiLeaks';
    const MAINTAINER = 'logmanoriginal';
    const PARAMETERS = [
        [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'title' => 'Select your category',
                'values' => [
                    'News' => '-News-',
                    'Leaks' => [
                        'All' => '-Leaks-',
                        'Intelligence' => '+-Intelligence-+',
                        'Global Economy' => '+-Global-Economy-+',
                        'International Politics' => '+-International-Politics-+',
                        'Corporations' => '+-Corporations-+',
                        'Government' => '+-Government-+',
                        'War & Military' => '+-War-Military-+'
                    ]
                ],
                'defaultValue' => 'news'
            ],
            'teaser' => [
                'name' => 'Show teaser',
                'type' => 'checkbox',
                'title' => 'If checked feeds will display the teaser',
                'defaultValue' => 'checked'
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());

        // News are presented differently
        switch ($this->getInput('category')) {
            case '-News-':
                $this->loadNewsItems($html);
                break;
            default:
                $this->loadLeakItems($html);
        }
    }

    public function getURI()
    {
        if (!is_null($this->getInput('category'))) {
            return static::URI . '/' . $this->getInput('category') . '.html';
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('category'))) {
            $category = array_search(
                $this->getInput('category'),
                static::PARAMETERS[0]['category']['values']
            );

            if ($category === false) {
                $category = array_search(
                    $this->getInput('category'),
                    static::PARAMETERS[0]['category']['values']['Leaks']
                );
            }

            return $category . ' - ' . static::NAME;
        }

        return parent::getName();
    }

    private function loadNewsItems($html)
    {
        $articles = $html->find('div.news-articles ul li');

        if (is_null($articles) || count($articles) === 0) {
            return;
        }

        foreach ($articles as $article) {
            $item = [];

            $item['title'] = $article->find('h3', 0)->plaintext;
            $item['uri'] = static::URI . $article->find('h3 a', 0)->href;
            $item['content'] = $article->find('div.introduction', 0)->plaintext;
            $item['timestamp'] = strtotime($article->find('div.timestamp', 0)->plaintext);

            $this->items[] = $item;
        }
    }

    private function loadLeakItems($html)
    {
        $articles = $html->find('li.tile');

        if (is_null($articles) || count($articles) === 0) {
            return;
        }

        foreach ($articles as $article) {
            $item = [];

            $item['title'] = $article->find('h2', 0)->plaintext;
            $item['uri'] = static::URI . $article->find('a', 0)->href;

            $teaser = static::URI . '/' . $article->find('div.teaser img', 0)->src;

            if ($this->getInput('teaser')) {
                $item['content'] = '<img src="'
                . $teaser
                . '" /><p>'
                . $article->find('div.intro', 0)->plaintext
                . '</p>';
            } else {
                $item['content'] = $article->find('div.intro', 0)->plaintext;
            }

            $item['timestamp'] = strtotime($article->find('div.timestamp', 0)->plaintext);
            $item['enclosures'] = [$teaser];

            $this->items[] = $item;
        }
    }
}
