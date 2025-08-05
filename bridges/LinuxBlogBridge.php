<?php

class LinuxBlogBridge extends BridgeAbstract
{
    const NAME = 'LinuxBlog.io';
    const URI = 'https://linuxblog.io';
    const DESCRIPTION = 'Retrieve recent articles';
    const MAINTAINER = 'tillcash';
    const CACHE_TIMEOUT = 60 * 60 * 6; // 6 hours
    const MAX_ARTICLES = 5;

    public function collectData()
    {
        $count = 0;
        $dom = getSimpleHTMLDOM(self::URI);
        $articles = $dom->find('ul.display-posts-listing li.listing-item');

        if (!$articles) {
            throwServerException('Failed to retrieve articles');
        }

        foreach ($articles as $article) {
            if ($count >= self::MAX_ARTICLES) {
                break;
            }

            $element = $article->find('a.title', 0);

            if (!$element || empty($element->plaintext) || empty($element->href)) {
                continue;
            }

            $timestamp = null;
            $url = $element->href;
            $date = $article->find('span.date', 0);

            if ($date && $date->plaintext) {
                $timestamp = strtotime($date->plaintext . ' 00:00:00 GMT');
            }

            $this->items[] = [
                'content'    => $this->constructContent($url),
                'timestamp'  => $timestamp,
                'title'      => trim($element->plaintext),
                'uid'        => $url,
                'uri'        => $url,
            ];

            $count++;
        }
    }

    private function constructContent($url)
    {
        $dom = getSimpleHTMLDOMCached($url);
        $article = $dom->find('section.entry.fix', 0);

        if (!$article) {
            return 'Content Not Found';
        }

        return $article->innertext;
    }
}
