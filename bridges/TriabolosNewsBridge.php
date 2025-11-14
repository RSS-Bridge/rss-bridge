<?php

declare(strict_types=1);

class TriabolosNewsBridge extends BridgeAbstract
{
    const NAME = 'Triabolos News';
    const URI = 'https://www.triabolos.de/';
    const DESCRIPTION = 'News feed of Hamburg Triathlon club Triabolos';
    const MAINTAINER = 't3sec';
    //const CACHE_TIMEOUT = 3600;
    const CACHE_TIMEOUT = 0; // seconds

    public function collectData()
    {
        $dom = getSimpleHTMLDOM('https://www.triabolos.de/news/stories/category/vereinsnachrichten');
        foreach ($dom->find('.blog-listing .blog-item') as $li) {
            $a = $li->find('.blog-content .blog-header .blog-title a', 0);
            $time = $li->find('.blog-content .blog-header .blog-intro time', 0);
            $category = $li->find('.blog-content .blog-header .blog-intro .category-name a', 0);
            $content = $li->find('.blog-content .blog-text p', 0);
            $enclosure = $li->find('.img-blog a img', 0);
            $this->items[] = [
                'title' => $a->plaintext,
                'content' => $content->plaintext,
                'timestamp' => $time->datetime,
                'categories' => [$category->plaintext],
                'enclosure' => is_null($enclosure) ? [] : ['https://www.triabolos.de' . $enclosure->src],
                'uri' => 'https://www.triabolos.de' . $a->href,
            ];
        }
    }
}