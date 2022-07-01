<?php

class SuperSmashBlogBridge extends BridgeAbstract
{
    const MAINTAINER = 'corenting';
    const NAME = 'Super Smash Blog';
    const URI = 'https://www.smashbros.com/en_US/blog/index.html';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'Latest articles from the Super Smash Blog blog';

    public function collectData()
    {
        $dlUrl = 'https://www.smashbros.com/data/bs/en_US/json/en_US.json';

        $jsonString = getContents($dlUrl);
        $json = json_decode($jsonString, true);

        foreach ($json as $article) {
            // Build content
            $picture = $article['acf']['image1']['url'];
            if (strlen($picture) != 0) {
                $picture = str_get_html('<img src="https://www.smashbros.com/' . substr($picture, 8) . '"/>');
            } else {
                $picture = '';
            }

            $video = $article['acf']['link_url'];
            if (strlen($video) != 0) {
                $video = str_get_html('<a href="' . $video . '">Youtube video</a>');
            } else {
                $video = '';
            }
            $text = str_get_html($article['acf']['editor']);
            $content = $picture . $video . $text;

            // Build final item
            $item = [];
            $item['title'] = $article['title']['rendered'];
            $item['timestamp'] = strtotime($article['date']);
            $item['content'] = $content;
            $item['uri'] = self::URI . '?post=' . $article['id'];

            $this->items[] = $item;
        }
    }
}
