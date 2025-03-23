<?php

class BruegelBridge extends BridgeAbstract
{
    const NAME = 'Bruegel';
    const URI = 'https://www.bruegel.org';
    const DESCRIPTION = 'European think-tank commentary and publications.';
    const MAINTAINER = 'KappaPrajd';
    const PARAMETERS = [
        [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'defaultValue' => '/publications',
                'values' => [
                    'Publications' => '/publications',
                    'Commentary' => '/commentary'
                ]
            ]
        ]
    ];

    public function getIcon()
    {
        return self::URI . '/themes/custom/bruegel/assets/favicon/android-icon-72x72.png';
    }

    public function collectData()
    {
        $url = self::URI . $this->getInput('category');
        $html = getSimpleHTMLDOM($url);

        $articles = $html->find('.c-listing__content article');

        foreach ($articles as $article) {
            $title = $article->find('.c-list-item__title a span', 0)->plaintext;
            $content = trim($article->find('.c-list-item__description', 0)->plaintext);
            $publishDate = $article->find('.c-list-item__date', 0)->plaintext;
            $href = $article->find('.c-list-item__title a', 0)->getAttribute('href');

            $item = [
                'title' => $title,
                'content' => $content,
                'timestamp' => strtotime($publishDate),
                'uri' => self::URI . $href,
                'author' => $this->getAuthor($article),
            ];

            $this->items[] = $item;
        }
    }

    private function getAuthor($article)
    {
        $authorsElements = $article->find('.c-list-item__authors a');

        $authors = array_map(function ($author) {
            return $author->plaintext;
        }, $authorsElements);

        return join(', ', $authors);
    }
}