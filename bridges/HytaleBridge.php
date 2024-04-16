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
    const _IMG_REGEX = '#https://cdn\.hytale\.com/\w+\.(?:jpg|png)#';

    public function collectData()
    {
        $blogPosts = json_decode(getContents(self::_API_URL_PUBLISHED));
        $length = count($blogPosts);

        for ($i = 0; $i < $length; $i += 3) {
            $slug = $blogPosts[$i]->slug;

            $blogPost = json_decode(getContents(self::_API_URL_BLOG_POST . $slug));

            if (property_exists($blogPost, 'next')) {
                $this->addBlogPost($blogPost->next);
            }

            $this->addBlogPost($blogPost);

            if (property_exists($blogPost, 'previous')) {
                $this->addBlogPost($blogPost->previous);
            }
        }

        if (($length >= 3) && ($length % 3 == 0)) {
            $slug = $blogPosts[$length - 1]->slug;

            $blogPost = json_decode(getContents(self::_API_URL_BLOG_POST . $slug));

            $this->addBlogPost($blogPost);
        }
    }

    private function addBlogPost($blogPost)
    {
        $item = [];

        $splittedTimestamp = explode('-', $blogPost->publishedAt);
        $year = $splittedTimestamp[0];
        $month = $splittedTimestamp[1];
        $slug = $blogPost->slug;
        $uri = 'https://hytale.com/news/' . $year . '/' . $month . '/' . $slug;

        $item['uri'] = $uri;
        $item['title'] = $blogPost->title;
        $item['author'] = $blogPost->author;
        $item['timestamp'] = $blogPost->publishedAt;
        $item['content'] = $blogPost->body;

        $blogCoverS3Key = $blogPost->coverImage->s3Key;
        $coverImagesURLs = [
            self::_BLOG_COVER_URL . $blogCoverS3Key,
            self::_BLOG_THUMB_URL . $blogCoverS3Key,
        ];

        if (preg_match_all(self::_IMG_REGEX, $blogPost->body, $bodyImagesURLs)) {
            $item['enclosures'] = array_merge($coverImagesURLs, $bodyImagesURLs[0]);
        } else {
            $item['enclosures'] = $coverImagesURLs;
        }

        $this->items[] = $item;
    }
}
