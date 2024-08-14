<?php

class EASeedBridge extends BridgeAbstract
{
    const NAME = 'EA Seed Blog';
    const URI = 'https://www.ea.com/seed';
    const DESCRIPTION = 'Posts from the EA Seed blog';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 86400; // 24h

    public function collectData()
    {
        $dom = getSimpleHTMLDOM(static::URI);
        $dom = $dom->find('ea-grid', 0);
        if (!$dom) {
            throw new \Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());
        foreach ($dom->find('ea-tile') as $article) {
            $a = $article->find('a', 0);
            $date = $article->find('div', 1)->plaintext;
            $title = $article->find('h3', 0)->plaintext;
            $author = $article->find('div', 0)->plaintext;

            $entry = getSimpleHTMLDOMCached($a->href, static::CACHE_TIMEOUT * 7 * 4);

            $content = $entry->find('main', 0);

            // remove header and links to other posts
            $content->find('ea-header', 0)->outertext = '';
            $content->find('ea-section', -1)->outertext = '';

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
