<?php

class OllamaBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Ollama Blog Bridge';
    const URI = 'https://ollama.com';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Returns latest blog posts from Ollama';

    const PARAMETERS = [
        '' => [
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'required' => true,
                'defaultValue' => 10
            ],
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . '/blog/');
        $limit = $this->getInput('limit');

        $posts = $html->find('main > section > a.group');
        for ($i = 0; $i < min(count($posts), $limit); $i++) {
            $post = $posts[$i];
            $title = $post->find('h2', 0)->plaintext;
            $date_text = $post->find('h3[datetime]', 0)->getAttribute('datetime');
            $timestamp = (new DateTime(mb_substr($date_text, 0, 19)))->format('U');
            $uri = self::URI . $post->getAttribute('href');
            $this->items[] = [
                'uri' => $uri,
                'title' => $title,
                'timestamp' => $timestamp,
                'content' => $this->parsePage($uri),
                'uid' => $uri
            ];
        }
    }

    private function parsePage($uri)
    {
        $html = getSimpleHTMLDOMCached(
            $uri,
            86400,
            [],
            [],
            true,
            true,
            DEFAULT_TARGET_CHARSET,
            false // Do not strip \n from <code> blocks
        );
        $contents = $html->find('main > article > section.prose', 0);
        $contents = defaultLinkTo($contents, self::URI);
        return $contents->innertext;
    }
}
