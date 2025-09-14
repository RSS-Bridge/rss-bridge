<?php

declare(strict_types=1);

class MagicTheGatheringBridge extends BridgeAbstract
{
    const NAME = 'Magic: The Gathering';
    const URI = 'https://magic.wizards.com/en/news/';
    const DESCRIPTION = 'Daily MTG - MTG News, Announcements, and Podcasts';
    const MAINTAINER = 'thefranke';
    const CACHE_TIMEOUT = 86400;

    const PARAMETERS = [
        [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'title' => 'News categories',
                'values' => [
                    'All' => 'archive',
                    'Annoucements' => 'annoucements',
                    'Card Image Gallery' => 'card-image-gallery',
                    'Card Preview' => 'card-preview',
                    'Feature' => 'feature',
                    'Magic Story' => 'magic-story',
                    'Making Magic' => 'making-magic',
                    'MTG Arena' => 'mtg-arena',
                ]
            ]
        ]
    ];

    public function collectData()
    {
        $url = static::URI . $this->getInput('category');

        $dom = getSimpleHTMLDOM($url);

        foreach ($dom->find('article') as $article) {
            $title = $article->find('h3', 0)->innertext;
            $author = $article->find('a', 2)->innertext;
            $articleurl = 'https://magic.wizards.com' . $article->find('a', 1)->href;

            $fullarticle = getSimpleHTMLDomCached($articleurl);
            $articlebody = $fullarticle->find('article', 0);
            $timestamp = strtotime($articlebody->find('time', 0)->innertext);
            $content = $articlebody->find('div.article-body', 0)->innertext;

            $this->items[] = [
                'title' => $title,
                'author' => $author,
                'uri' => $articleurl,
                'content' => $content,
                'timestamp' => $timestamp,
            ];
        }
    }
}
