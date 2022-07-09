<?php

class HytaleBridge extends BridgeAbstract
{
    const NAME = 'Hytale Bridge';
    const URI = 'https://hytale.com/news';
    const DESCRIPTION = 'All blog posts from Hytale\'s news blog.';
    const MAINTAINER = 'llamasblade';

    const _API_URL_PUBLISHED = 'https://hytale.com/api/blog/post/published';
    const _API_URL_BLOG_POST = 'https://hytale.com/api/blog/post/slug/';
    const _BLOG_THUMB_URL = 'https://cdn.hytale.com/variants/blog_thumb_';
    const _BLOG_COVER_URL = 'https://cdn.hytale.com/variants/blog_cover_';
    const _IMG_REGEX = '/https:\/\/cdn\.hytale\.com\/\w+\.(?:jpg|png)/';

    public function collectData()
    {
        $blog_posts = json_decode(file_get_contents(self::_API_URL_PUBLISHED));

        foreach ($blog_posts as $blog_post) {
            $item = [];

            $splitted_timestamp = explode('-', $blog_post->publishedAt);
            $year = $splitted_timestamp[0];
            $month = $splitted_timestamp[1];
            $slug = $blog_post->slug;

            $uri = 'https://hytale.com/news/' . $year . '/' . $month . '/' . $slug;

            $item['uri'] = $uri;
            $item['title'] = $blog_post->title;
            $item['author'] = $blog_post->author;
            $item['timestamp'] = $blog_post->publishedAt;

            $blog_post_full = json_decode(file_get_contents(self::_API_URL_BLOG_POST . $slug));

            $item['content'] = $blog_post_full->body;
            $blog_cover_s3_key = $blog_post_full->coverImage->s3Key;

            $cover_images_urls = [
                self::_BLOG_COVER_URL . $blog_cover_s3_key,
                self::_BLOG_THUMB_URL . $blog_cover_s3_key,
            ];

            if (preg_match_all(self::_IMG_REGEX, $blog_post_full->body, $body_images_urls)) {
                $item['enclosures'] = array_merge($cover_images_urls, $body_images_urls[0]);
            } else {
                $item['enclosures'] = $cover_images_urls;
            }

            $this->items[] = $item;
        }
    }
}
