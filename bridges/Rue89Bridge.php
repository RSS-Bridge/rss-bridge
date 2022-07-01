<?php

class Rue89Bridge extends BridgeAbstract
{
    const MAINTAINER = 'teromene';
    const NAME = 'Rue89';
    const URI = 'https://www.nouvelobs.com/rue89/';
    const DESCRIPTION = 'Returns the newest posts from Rue89';

    public function collectData()
    {
        $jsonArticles = getContents('https://appdata.nouvelobs.com/rue89/feed.json');
        $articles = json_decode($jsonArticles)->items;
        foreach ($articles as $article) {
            $this->items[] = $this->getArticle($article);
        }
    }

    private function getArticle($articleInfo)
    {
        $articleJson = getContents($articleInfo->json_url);
        $article = json_decode($articleJson);
        $item = [];
        $item['title'] = $article->title;
        $item['uri'] = $article->url;
        if ($article->content_premium !== null) {
            $item['content'] = $article->content_premium;
        } else {
            $item['content'] = $article->content;
        }
        $item['timestamp'] = $article->date_publi;
        $item['author'] = $article->author->show_name;

        $item['enclosures'] = [];
        foreach ($article->images as $image) {
            $item['enclosures'][] = $image->url;
        }

        $item['categories'] = [];
        foreach ($article->categories as $category) {
            $item['categories'][] = $category->title;
        }

        return $item;
    }
}
