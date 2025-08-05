<?php

class DevToBridge extends BridgeAbstract
{
    const CONTEXT_BY_TAG = 'By tag';
    const CONTEXT_BY_USER = 'By user';

    const NAME = 'dev.to Bridge';
    const URI = 'https://dev.to';
    const DESCRIPTION = 'Returns feeds for tags';
    const MAINTAINER = 'logmanoriginal';
    const CACHE_TIMEOUT = 10800; // 15 min.

    const PARAMETERS = [
        self::CONTEXT_BY_TAG => [
            'tag' => [
                'name' => 'Tag',
                'type' => 'text',
                'required' => true,
                'title' => 'Insert your tag',
                'exampleValue' => 'python'
            ],
            'full' => [
                'name' => 'Full article',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Enable to receive the full article for each item'
            ]
            ],
        self::CONTEXT_BY_USER => [
            'user' => [
                'name' => 'User',
                'type' => 'text',
                'required' => true,
                'title' => 'Insert your username',
                'exampleValue' => 'n3wt0n'
            ],
            'full' => [
                'name' => 'Full article',
                'type' => 'checkbox',
                'required' => false,
                'title' => 'Enable to receive the full article for each item'
            ]
        ]
    ];

    public function getURI()
    {
        switch ($this->queriedContext) {
            case self::CONTEXT_BY_TAG:
                if ($tag = $this->getInput('tag')) {
                    return static::URI . '/t/' . urlencode($tag);
                }
                break;
            case self::CONTEXT_BY_USER:
                if ($user = $this->getInput('user')) {
                    return static::URI . '/' . urlencode($user);
                }
                break;
        }

        return parent::getURI();
    }

    public function getIcon()
    {
        return 'https://practicaldev-herokuapp-com.freetls.fastly.net/assets/
apple-icon-5c6fa9f2bce280428589c6195b7f1924206a53b782b371cfe2d02da932c8c173.png';
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOMCached($this->getURI());

        $html = defaultLinkTo($html, static::URI);

        $articles = $html->find('div.crayons-story')
            or throwServerException('Could not find articles!');

        foreach ($articles as $article) {
            $item = [];

            $item['uri'] = $article->find('a[id*=article-link]', 0)->href;
            $item['title'] = $article->find('h2 > a', 0)->plaintext;

            $item['timestamp'] = $article->find('time', 0)->datetime;
            $item['author'] = $article->find('a.crayons-story__secondary.fw-medium', 0)->plaintext;

            // Profile image
            $item['enclosures'] = [$article->find('img', 0)->src];

            if ($this->getInput('full')) {
                $fullArticle = $this->getFullArticle($item['uri']);
                $item['content'] = <<<EOD
<p>{$fullArticle}</p>
EOD;
            } else {
                $item['content'] = <<<EOD
<img src="{$item['enclosures'][0]}" alt="{$item['author']}">
<p>{$item['title']}</p>
EOD;
            }

            // categories
            foreach ($article->find('a.crayons-tag') as $tag) {
                $item['categories'][] = str_replace('#', '', $tag->plaintext);
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('tag'))) {
            return ucfirst($this->getInput('tag')) . ' - dev.to';
        }

        return parent::getName();
    }

    private function getFullArticle($url)
    {
        $html = getSimpleHTMLDOMCached($url);

        $html = defaultLinkTo($html, static::URI);

        if ($html->find('div.crayons-article__cover', 0)) {
            return $html->find('div.crayons-article__cover', 0) . $html->find('[id="article-body"]', 0);
        }

        return $html->find('[id="article-body"]', 0);
    }
}
