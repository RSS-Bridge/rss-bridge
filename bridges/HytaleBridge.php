<?php

class HytaleBridge extends BridgeAbstract
{
    const NAME = 'Hytale';
    const URI = 'https://hytale.com/news';
    const DESCRIPTION = 'All blog posts from Hytale\'s news blog.';
    const MAINTAINER = 'orionblur';

    const _CLASS_WITH_ARTICLES = 'space-y-0';
    const _DESCRIPTION_ELEMENT = 'span.line-clamp-4';
    const _AUTHOR_ELEMENT = 'span.text-right';

    public function collectData()
    {
        $siteDOM = getSimpleHTMLDOM(self::URI);
        $articlesContainer = $siteDOM->find('div.' . self::_CLASS_WITH_ARTICLES, 0);
        if (!$articlesContainer) {
            return;
        }
        foreach ($articlesContainer->find('article') as $article) {
            $this->addBlogPost($article);
        }
    }

    private function addBlogPost($blogPost)
    {
        $item = [];

        $link = $blogPost->find('h4 a', 0);
        if (!$link) {
            return;
        }

        $articlePath = $link->getAttribute('href');
        $item['uri'] = 'https://hytale.com' . $articlePath;
        $item['title'] = trim($link->plaintext);

        $descriptionElement = $blogPost->find(self::_DESCRIPTION_ELEMENT, 0);
        if ($descriptionElement) {
            $item['content'] = trim($descriptionElement->plaintext);
        }

        $imgElement = $blogPost->find('img', 0);
        if ($imgElement) {
            $imageUrl = $imgElement->getAttribute('src');

            if ($imageUrl) {
                $imageHtml = '<img src="' . $imageUrl . '" alt="Article thumbnail" />';

                if (isset($item['content'])) {
                    $item['content'] = $imageHtml . '<br />' . $item['content'];
                } else {
                    $item['content'] = $imageHtml;
                }
            }
        }

        $footerSpans = $blogPost->find('span.flex.flex-row.gap-2 > span');

        if (count($footerSpans) >= 1) {
            $dateText = trim($footerSpans[0]->plaintext);
            $item['timestamp'] = strtotime($dateText);
        }

        $authorElement = $blogPost->find(self::_AUTHOR_ELEMENT, 0);
        if ($authorElement) {
            $authorText = trim($authorElement->plaintext);

            if (preg_match('/Posted by\s+(.+)/i', $authorText, $matches)) {
                $item['author'] = trim($matches[1]);
            }
        }

        $item['uid'] = md5($articlePath);

        $this->items[] = $item;
    }
}
