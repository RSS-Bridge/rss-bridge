<?php

class HytaleBridge extends BridgeAbstract
{
    const NAME = 'Hytale';
    const URI = 'https://hytale.com/news';
    const DESCRIPTION = 'All blog posts from Hytale\'s news blog.';
    const MAINTAINER = 'orionblur';

    const _CLASS_WITH_ARTICLES = 'space-y-0';
    const _DESCRIPTION_ELEMENT = 'span.line-clamp-4';
    const _FOOTER_ELEMENT = 'span.flex.flex-row.gap-2 span';

    public function collectData()
    {
        $siteDOM = getSimpleHTMLDOM(self::URI);
        $articlesContainer = $siteDOM->find('div.' . self::_CLASS_WITH_ARTICLES, 0);
        if (!$articlesContainer) {
            return;
        }
        $articles = $articlesContainer->find('article');
        foreach ($articles as $article) {
            $this->addBlogPost($article);
        }
    }

    private function addBlogPost($blogPost)
    {
        $link = $blogPost->find('a', 0);
        if (!$link) {
            return;
        }

        $item = [];

        $articlePath = $link->getAttribute('href');
        $item['uri'] = 'https://hytale.com' . $articlePath;

        $titleElement = $link->find('h4', 0);
        if ($titleElement) {
            $item['title'] = trim($titleElement->plaintext);
        }

        $descriptionElement = $link->find(self::_DESCRIPTION_ELEMENT, 0);
        if ($descriptionElement) {
            $item['content'] = trim($descriptionElement->plaintext);
        }

        $imgElement = $link->find('img', 0);
        if ($imgElement) {
            $imageUrl = $imgElement->getAttribute('src');
            if ($imageUrl && isset($item['content'])) {
                $item['content'] = '<img src="' . $imageUrl . '" alt="Article thumbnail" /><br />' . $item['content'];
            } elseif ($imageUrl) {
                $item['content'] = '<img src="' . $imageUrl . '" alt="Article thumbnail" />';
            }
        }

        $footerSpans = $link->find(self::_FOOTER_ELEMENT);
        if (count($footerSpans) >= 2) {
            $dateText = trim($footerSpans[0]->plaintext);
            $item['timestamp'] = strtotime($dateText);

            $authorText = trim($footerSpans[1]->plaintext);
            if (preg_match('/Posted by (.+)/', $authorText, $matches)) {
                $item['author'] = trim($matches[1]);
            }
        }

        $item['uid'] = md5($articlePath);

        $this->items[] = $item;
    }
}
