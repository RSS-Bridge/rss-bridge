<?php

class HuntShowdownNewsBridge extends BridgeAbstract
{
    const NAME = 'Hunt Showdown News Bridge';
    const MAINTAINER = 'deffy92';
    const URI = 'https://www.huntshowdown.com';
    const DESCRIPTION = 'Returns the latest news from HuntShowdown.com/news';
    const BASE_URI = 'https://www.huntshowdown.com/';

    public function collectData()
    {
        $html = getSimpleHTMLDOM('https://www.huntshowdown.com/news/tagged/news');
        $articles = defaultLinkTo($html, self::URI)->find('.col');

        // Removing first element because it's a "load more" button
        array_shift($articles);
        foreach ($articles as $article) {
            $item = [];

            $article_title = $article->find('h3', 0)->plaintext;
            $article_content = $article->find('p', 0)->plaintext;
            $article_cover = $article->find('img', 0)->src;

            // If there is a cover, add it to the content
            if (!empty($article_cover)) {
                $article_cover = '<img src="' . $article_cover . '" alt="' . $article_title . '"> <br/> <br/>';
                $article_content = $article_cover . $article_content;
            }

            $item['uri'] = $article->find('a', 0)->href;
            $item['title'] = $article_title;
            $item['content'] = $article_content;
            $item['enclosures'] = [$article_cover];
            $item['timestamp'] = $article->find('span', 0)->plaintext;

            $this->items[] = $item;
        }
    }
}