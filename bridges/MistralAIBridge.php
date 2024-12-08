<?php

class MistralAIBridge extends BridgeAbstract
{
    const MAINTAINER = 'sqrtminusone';
    const NAME = 'Mistral AI Bridge';
    const URI = 'https://mistral.ai/';

    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Returns blog posts from Mistral AI';

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
        $html = getSimpleHTMLDOM(self::URI . 'news/');
        $limit = $this->getInput('limit');

        $posts = $html->find('article.news-card');
        for ($i = 0; $i < min($limit, count($posts)); $i++) {
            $post = $posts[$i];
            $url = self::URI . $post->find('a', 0)->href;
            $this->parsePage($url);
        }
    }

    private function parsePage($url)
    {
        $html = getSimpleHTMLDOMCached($url, 7 * 24 * 60 * 60);
        $title = $html->find('h1.hero-title', 0)->plaintext;
        $timestamp_tag = $html->find('i.ti-calendar', 0)->parent;
        $timestamp = DateTime::createFromFormat('F j, Y', $timestamp_tag->plaintext)->format('U');

        $content = '';

        // Subheader
        $header = $html->find('p.hero-description', 0);
        if ($header != null) {
            $content .= $header->outertext;
        }

        // Main content
        $main = $html->find('$article > div.content', 0);

        // Mostly YouTube videos
        $iframes = $main->find('iframe');
        foreach ($iframes as $iframe) {
            $iframe->parent->removeAttribute('style');
            $iframe->outertext = '<a href="' . $iframe->src . '">' . $iframe->src . '</a>';
        }

        $main = defaultLinkTo($main, self::URI);
        $content .= $main;
        $this->items[] = [
            'title' => $title,
            'timestamp' => $timestamp,
            'content' => $content,
            'uri' => $url,
        ];
    }
}
