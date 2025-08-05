<?php

class UrlebirdBridge extends BridgeAbstract
{
    const MAINTAINER = 'dotter-ak';
    const NAME = 'urlebird.com';
    const URI = 'https://urlebird.com/';
    const DESCRIPTION = 'Bridge for urlebird.com';
    const CACHE_TIMEOUT = 60 * 5;
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

    public function collectData()
    {
        switch ($this->getInput('query')[0]) {
            case '@':
                $url = 'https://urlebird.com/user/' . substr($this->getInput('query'), 1) . '/';
                break;
            case '#':
                $url = 'https://urlebird.com/hash/' . substr($this->getInput('query'), 1) . '/';
                break;
            default:
                throwServerException('Please, enter valid username or hashtag!');
                break;
        }

        $html = getSimpleHTMLDOM($url);
        $limit = 10;

        $this->title = $html->find('title', 0)->innertext;
        $articles = $html->find('div.thumb');
        $articles = array_slice($articles, 0, $limit);
        foreach ($articles as $article) {
            $item = [];
            $itemUrl = $article->find('a', 2)->href;
            $item['uri'] = $this->encodePathSegments($itemUrl);

            $dom = getSimpleHTMLDOM($item['uri']);
            $videoDiv = $dom->find('div.video', 0);

            // timestamp
            $timestampH6 = $videoDiv->find('h6', 0);
            $datetimeString = str_replace('Posted ', '', $timestampH6->plaintext);
            $item['timestamp'] = $datetimeString;

            $innertext = $dom->find('a.user-video', 1)->innertext;
            $alt = $article->find('img', 0)->alt;
            $item['author'] = $alt . ' (' . $innertext . ')';

            $item['title'] = $dom->find('title', 0)->innertext;
            $item['enclosures'][] = $dom->find('video', 0)->poster;

            $video = $dom->find('video', 0);
            $video->autoplay = null;

            $item['content'] = $video->outertext . '<br>' .
                $dom->find('div.music', 0) . '<br>' .
                $dom->find('div.info2', 0)->innertext .
                '<br><br><a href="' . $dom->find('video', 0)->src .
                '">Direct video link</a><br><br><a href="' . $item['uri'] .
                '">Post link</a><br><br>';

            $this->items[] = $item;
        }
    }

    private function encodePathSegments($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $pathSegments = explode('/', $path);
        $encodedPathSegments = array_map('urlencode', $pathSegments);
        $encodedPath = implode('/', $encodedPathSegments);
        $result = str_replace($path, $encodedPath, $url);
        return $result;
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
