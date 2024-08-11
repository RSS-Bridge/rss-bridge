<?php

class ActivisionResearchBridge extends BridgeAbstract
{
    const NAME = 'Activision Research Blog';
    const URI = 'https://research.activision.com';
    const DESCRIPTION = 'Posts from the Activision Research blog';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 86400; // 24h

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(static::URI);
        $dom = $dom->find('div[id="home-blog-feed"]', 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('div[class="blog-entry"]') as $article) {
            $a = $article->find('a', 0);

            $blogimg = extractFromDelimiters($article->find('div[class="blog-img"]', 0)->style, 'url(', ')');

            $title = htmlspecialchars_decode($article->find('div[class="title"]', 0)->plaintext);
            $author = htmlspecialchars_decode($article->find('div[class="author]', 0)->plaintext);
            $date = $article->find('div[class="pubdate"]', 0)->plaintext;

            $entry = getSimpleHTMLDOMCached($a->href, static::CACHE_TIMEOUT * 7 * 4);
            $entry = defaultLinkTo($entry, $this->getURI());

            $content = $entry->find('div[class="blog-body"]', 0);
            $tagsremove = ['script', 'iframe', 'input', 'form'];
            $content = sanitize($content, $tagsremove);
            $content = '<img src="' . static::URI . $blogimg . '" alt="">' . $content;

            $this->items[] = [
                'title' => $title,
                'author' => $author,
                'uri' => $a->href,
                'content' => $content,
                'timestamp' => strtotime($date),
            ];
        }
    }
}
