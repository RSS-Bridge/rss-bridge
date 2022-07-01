<?php

class OpenlyBridge extends BridgeAbstract
{
    const NAME = 'Openly Bridge';
    const URI = 'https://www.openlynews.com/';
    const DESCRIPTION = 'Returns news articles';
    const MAINTAINER = 'VerifiedJoseph';
    const PARAMETERS = [
        'All News' => [],
        'All Opinion' => [],
        'By Region' => [
            'region' => [
                'name' => 'Region',
                'type' => 'list',
                'values' => [
                    'Africa' => 'africa',
                    'Asia Pacific' => 'asia-pacific',
                    'Europe' => 'europe',
                    'Latin America' => 'latin-america',
                    'Middle Easta' => 'middle-east',
                    'North America' => 'north-america'
                ]
            ],
            'content' => [
                'name' => 'Content',
                'type' => 'list',
                'values' => [
                    'News' => 'news',
                    'Opinion' => 'people'
                ],
                'defaultValue' => 'news'
            ]
        ],
        'By Tag' => [
            'tag' => [
                'name' => 'Tag',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'lgbt-law',
            ],
            'content' => [
                'name' => 'Content',
                'type' => 'list',
                'values' => [
                    'News' => 'news',
                    'Opinion' => 'people'
                ],
                'defaultValue' => 'news'
            ]
        ],
        'By Author' => [
            'profileId' => [
                'name' => 'Profile ID',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '003D000002WZGYRIA5',
            ]
        ]
    ];

    const TEST_DETECT_PARAMETERS = [
        'https://www.openlynews.com/profile/?id=0033z00002XUTepAAH' => [
            'context' => 'By Author', 'profileId' => '0033z00002XUTepAAH'
        ],
        'https://www.openlynews.com/news/?page=1&theme=lgbt-law' => [
            'context' => 'By Tag', 'content' => 'news', 'tag' => 'lgbt-law'
        ],
        'https://www.openlynews.com/news/?page=1&region=north-america' => [
            'context' => 'By Region', 'content' => 'news', 'region' => 'north-america'
        ],
        'https://www.openlynews.com/news/?theme=lgbt-law' => [
            'context' => 'By Tag', 'content' => 'news', 'tag' => 'lgbt-law'
        ],
        'https://www.openlynews.com/news/?region=north-america' => [
            'context' => 'By Region', 'content' => 'news', 'region' => 'north-america'
        ]
    ];

    const CACHE_TIMEOUT = 900; // 15 mins
    const ARTICLE_CACHE_TIMEOUT = 3600; // 1 hour

    private $feedTitle = '';
    private $itemLimit = 10;

    private $profileUrlRegex = '/openlynews\.com\/profile\/\?id=([a-zA-Z0-9]+)/';
    private $tagUrlRegex = '/openlynews\.com\/([a-z]+)\/\?(?:page=(?:[0-9]+)&)?theme=([\w-]+)/';
    private $regionUrlRegex = '/openlynews\.com\/([a-z]+)\/\?(?:page=(?:[0-9]+)&)?region=([\w-]+)/';

    public function detectParameters($url)
    {
        $params = [];

        if (preg_match($this->profileUrlRegex, $url, $matches) > 0) {
            $params['context'] = 'By Author';
            $params['profileId'] = $matches[1];
            return $params;
        }

        if (preg_match($this->tagUrlRegex, $url, $matches) > 0) {
            $params['context'] = 'By Tag';
            $params['content'] = $matches[1];
            $params['tag'] = $matches[2];
            return $params;
        }

        if (preg_match($this->regionUrlRegex, $url, $matches) > 0) {
            $params['context'] = 'By Region';
            $params['content'] = $matches[1];
            $params['region'] = $matches[2];
            return $params;
        }

        return null;
    }

    public function collectData()
    {
        $url = $this->getAjaxURI();

        if ($this->queriedContext === 'By Author') {
            $url = $this->getURI();
        }

        $html = getSimpleHTMLDOM($url);
        $html = defaultLinkTo($html, $this->getURI());

        if ($html->find('h1', 0)) {
            $this->feedTitle = $html->find('h1', 0)->plaintext;
        }

        if ($html->find('h2.title-v4', 0)) {
            $html->find('span.tooltiptext', 0)->innertext = '';
            $this->feedTitle = $html->find('a.tooltipitem', 0)->plaintext;
        }

        $items = $html->find('div.item');
        $limit = 5;
        foreach (array_slice($items, 0, $limit) as $div) {
            $this->items[] = $this->getArticle($div->find('a', 0)->href);

            if (count($this->items) >= $this->itemLimit) {
                break;
            }
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'All News':
                return self::URI . 'news';
                break;
            case 'All Opinion':
                return self::URI . 'people';
                break;
            case 'By Tag':
                return self::URI . $this->getInput('content') . '/?theme=' . $this->getInput('tag');
            case 'By Region':
                return self::URI . $this->getInput('content') . '/?region=' . $this->getInput('region');
                break;
            case 'By Author':
                return self::URI . 'profile/?id=' . $this->getInput('profileId');
                break;
            default:
                return parent::getURI();
        }
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'All News':
                return 'News - Openly';
                break;
            case 'All Opinion':
                return 'Opinion - Openly';
                break;
            case 'By Tag':
                if (empty($this->feedTitle)) {
                    $this->feedTitle = $this->getInput('tag');
                }

                if ($this->getInput('content') === 'people') {
                    return $this->feedTitle . ' - Opinion - Openly';
                }

                return $this->feedTitle . ' - Openly';
                break;
            case 'By Region':
                if (empty($this->feedTitle)) {
                    $this->feedTitle = $this->getInput('region');
                }

                if ($this->getInput('content') === 'people') {
                    return $this->feedTitle . ' - Opinion - Openly';
                }

                return $this->feedTitle . ' - Openly';
                break;
            case 'By Author':
                if (empty($this->feedTitle)) {
                    $this->feedTitle = $this->getInput('profileId');
                }

                return $this->feedTitle . ' - Author - Openly';
                break;
            default:
                return parent::getName();
        }
    }

    private function getAjaxURI()
    {
        $part = '/ajax.html?';

        switch ($this->queriedContext) {
            case 'All News':
                return self::URI . 'news' . $part;
                break;
            case 'All Opinion':
                return self::URI . 'people' . $part;
                break;
            case 'By Tag':
                return self::URI . $this->getInput('content') . $part . 'theme=' . $this->getInput('tag');
                break;
            case 'By Region':
                return self::URI . $this->getInput('content') . $part . 'region=' . $this->getInput('region');
                break;
        }
    }

    private function getArticle($url)
    {
        $article = getSimpleHTMLDOMCached($url, self::ARTICLE_CACHE_TIMEOUT);
        $article = defaultLinkTo($article, $this->getURI());

        $item = [];
        $item['title'] = $article->find('h1', 0)->plaintext;
        $item['uri'] = $url;
        $item['content'] = $article->find('div.body-text', 0);
        $item['enclosures'][] = $article->find('meta[name="twitter:image"]', 0)->content;
        $item['timestamp'] = $article->find('div.meta.small', 0)->plaintext;

        if ($article->find('div.meta a', 0)) {
            $item['author'] = $article->find('div.meta a', 0)->plaintext;
        }

        foreach ($article->find('div.themes li') as $li) {
            $item['categories'][] = trim(htmlspecialchars($li->plaintext, ENT_QUOTES));
        }

        return $item;
    }
}
