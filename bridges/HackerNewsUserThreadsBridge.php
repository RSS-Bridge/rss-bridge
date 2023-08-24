<?php

class HackerNewsUserThreadsBridge extends BridgeAbstract
{
    const MAINTAINER = 'rakoo';
    const NAME = 'Hacker News User Threads';
    const URI = 'https://news.ycombinator.com';
    const CACHE_TIMEOUT = 7200; // 2 hours
    const DESCRIPTION = 'Hacker News threads for a user (at https://news.ycombinator.com/threads?id=xxx)';
    const PARAMETERS = [ [
        'user' => [
            'name' => 'User',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'nixcraft',
            'title' => 'User whose threads you want to see'
        ]
    ]];

    public function collectData()
    {
        $url = 'https://news.ycombinator.com/threads?id=' . $this->getInput('user');
        $html = getSimpleHTMLDOM($url);
        Debug::log('queried ' . $url);
        Debug::log('found ' . $html);

        $item = [];
        $articles = $html->find('tr[class*="comtr"]');
        $story = '';

        foreach ($articles as $element) {
            $id = $element->getAttribute('id');
            $item['uri'] = 'https://news.ycombinator.com/item?id=' . $id;

            $author = $element->find('span[class*="comhead"]', 0)->find('a[class="hnuser"]', 0)->innertext;
            $newstory = $element->find('span[class*="comhead"]', 0)->find('span[class="onstory"]', 0);
            if (count($newstory->find('a')) > 0) {
                $story = $newstory->find('a', 0)->innertext;
            }

            $title = $author . ' | on ' . $story;
            $item['author'] = $author;
            $item['title'] = $title;
            $item['timestamp'] = $element->find('span[class*="age"]', 0)->find('a', 0)->innertext;
            $item['content'] = $element->find('span[class*="commtext"]', 0)->innertext;

            $this->items[] = $item;
        }
    }
}
