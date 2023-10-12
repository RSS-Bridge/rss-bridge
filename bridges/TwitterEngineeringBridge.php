<?php

class TwitterEngineeringBridge extends FeedExpander
{
    const MAINTAINER = 'corenting';
    const NAME = 'Twitter Engineering Blog';
    const URI = 'https://blog.twitter.com/engineering/';
    const DESCRIPTION = 'Returns the newest articles.';
    const CACHE_TIMEOUT = 21600; // 6h

    public function collectData()
    {
        $url = 'https://blog.twitter.com/engineering/en_us/blog.rss';
        $this->collectExpandableDatas($url);
    }

    protected function parseItem(array $item)
    {
        $dom = getSimpleHTMLDOMCached($item['uri']);
        if (!$dom) {
            $item['content'] .= '<p><em>Could not request ' . $this->getName() . ': ' . $item['uri'] . '</em></p>';
            return $item;
        }
        $dom = defaultLinkTo($dom, $this->getURI());

        $article_body = $dom->find('div.column.column-6', 0);

        // Remove elements that are not part of article content
        $unwanted_selector = 'div.bl02-blog-post-text-masthead, div.tweet-error-text, div.bl13-tweet-template';
        foreach ($article_body->find($unwanted_selector) as $found) {
            $found->outertext = '';
        }

        // Set src for images
        foreach ($article_body->find('img') as $found) {
            $found->setAttribute('src', $found->getAttribute('data-src'));
        }

        $item['content'] = $article_body;
        $item['timestamp'] = strtotime($dom->find('span.b02-blog-post-no-masthead__date', 0)->innertext);
        $item['categories'] = self::getCategoriesFromTags($dom);

        return $item;
    }

    private static function getCategoriesFromTags($article_html)
    {
        $tags_list_items = [$article_html->find('.post__tags > ul > li')];
        $categories = [];

        foreach ($tags_list_items as $tag_list_item) {
            foreach ($tag_list_item as $tag) {
                $categories[] = trim($tag->plaintext);
            }
        }

        return $categories;
    }

    public function getName()
    {
        // Else the original feed returns "English (US)" as the title
        return 'Twitter Engineering Blog';
    }
}
