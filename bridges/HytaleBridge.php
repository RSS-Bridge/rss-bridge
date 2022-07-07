<?php

class HytaleBridge extends BridgeAbstract
{
    const NAME = 'Hytale Bridge';
    const URI = 'https://hytale.com/news';
    const DESCRIPTION = 'All blog posts from Hytale\'s news blog.';
    const MAINTAINER = 'llamasblade';

    const _API_URL = 'https://hytale.com/api/blog/post/published';

    public function collectData()
    {
        $blog_posts = json_decode(file_get_contents(self::_API_URL));

        foreach ($blog_posts as $blog_post) {
            $item = [];

            $splitted_timestamp = explode('-', $blog_post->publishedAt);
            $year = $isodatetime[0];
            $month = $isodatetime[1];
            $slug = $blog_post->slug;

            $uri = 'https://hytale.com/news/' . $year . '/' . $month . '/' . $slug;

            $item['uri'] = $uri;
            $item['title'] = $blog_post->title;
            $item['author'] = $blog_post->author;
            $item['timestamp'] = $blog_post->publishedAt;
            $item['content'] = $blog_post->bodyExcerpt;

            $this->$items[] = $item;
        }
    }
}
