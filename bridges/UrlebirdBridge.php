<?php

class UrlebirdBridge extends BridgeAbstract
{
    const MAINTAINER = 'dotter-ak';
    const NAME = 'urlebird.com';
    const URI = 'https://urlebird.com/';
    const DESCRIPTION = 'Bridge for urlebird.com';
    const CACHE_TIMEOUT = 10;
    const PARAMETERS = [
        [
            'query' => [
                'name' => '@username or #hashtag',
                'type' => 'text',
                'required' => true,
                'exampleValue' => '@willsmith',
                'title' => '@username or #hashtag'
            ]
        ]
    ];

    private $title;

    private function fixURI($uri)
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        return str_replace($path, implode('/', $encoded_path), $uri);
    }

    public function collectData()
    {
        switch ($this->getInput('query')[0]) {
            default:
                returnServerError('Please, enter valid username or hashtag!');
                break;
            case '@':
                $url = 'https://urlebird.com/user/' . substr($this->getInput('query'), 1) . '/';
                break;
            case '#':
                $url = 'https://urlebird.com/hash/' . substr($this->getInput('query'), 1) . '/';
                break;
        }

        $html = getSimpleHTMLDOM($url);
        $this->title = $html->find('title', 0)->innertext;
        $articles = $html->find('div.thumb');
        foreach ($articles as $article) {
            $item = [];
            $item['uri'] = $this->fixURI($article->find('a', 2)->href);
            $article_content = getSimpleHTMLDOM($item['uri']);
            $item['author'] = $article->find('img', 0)->alt . ' (' .
                $article_content->find('a.user-video', 1)->innertext . ')';
            $item['title'] = $article_content->find('title', 0)->innertext;
            $item['enclosures'][] = $article_content->find('video', 0)->poster;
            $video = $article_content->find('video', 0);
            $video->autoplay = null;
            $item['content'] = $video->outertext . '<br>' .
                $article_content->find('div.music', 0) . '<br>' .
                $article_content->find('div.info2', 0)->innertext .
                '<br><br><a href="' . $article_content->find('video', 0)->src .
                '">Direct video link</a><br><br><a href="' . $item['uri'] .
                '">Post link</a><br><br>';
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        return $this->title ?: parent::getName();
    }

    public function getIcon()
    {
        return 'https://urlebird.com/favicon.ico';
    }
}
