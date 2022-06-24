<?php

class RoadAndTrackBridge extends BridgeAbstract
{
    const MAINTAINER = 'teromene';
    const NAME = 'Road And Track Bridge';
    const URI = 'https://www.roadandtrack.com/';
    const CACHE_TIMEOUT = 86400; // 24h
    const DESCRIPTION = 'Returns the latest news from Road & Track.';

    public function collectData()
    {

        $page = getSimpleHTMLDOM(self::URI);

        $limit = 5;

        foreach ($page->find('a.enk2x9t2') as $article) {
            $this->items[] = $this->fetchArticle($article->href);

            if (count($this->items) >= $limit) {
                break;
            }
        }
    }

    private function fixImages($content)
    {

        $enclosures = array();
        foreach ($content->find('img') as $image) {
            $image->src = explode('?', $image->getAttribute('data-src'))[0];
            $enclosures[] = $image->src;
        }

        foreach ($content->find('.embed-image-wrap, .content-lede-image-wrap') as $imgContainer) {
            $imgContainer->style = '';
        }

        return $enclosures;
    }

    private function fetchArticle($articleLink)
    {

        $articleLink = self::URI . $articleLink;
        $article = getSimpleHTMLDOM($articleLink);
        $item = array();

        $title = $article->find('.content-hed', 0);
        if ($title) {
            $item['title'] = $title->innertext;
        }

        $item['author'] = $article->find('.byline-name', 0)->innertext;
        $item['timestamp'] = strtotime($article->find('.content-info-date', 0)->getAttribute('datetime'));

        $content = $article->find('.content-container', 0);
        if ($content->find('.content-rail', 0) !== null) {
            $content->find('.content-rail', 0)->innertext = '';
        }

        $enclosures = $this->fixImages($content);

        $item['enclosures'] = $enclosures;
        $item['content'] = $content;
        return $item;
    }

    private function getArticleContent($article)
    {

        return getContents($article->contentUrl);
    }
}
