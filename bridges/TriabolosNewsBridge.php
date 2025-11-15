<?php

declare(strict_types=1);

class TriabolosNewsBridge extends BridgeAbstract
{
    const CATEGORIES      = [
        'Alle' => 'stories',
        'Vereinsnachrichten' => 'stories/category/vereinsnachrichten',
        'Eilmeldungen'   => 'stories/category/eilmeldungen',
        'Neue Mitglieder'      => 'stories/category/neue%20mitglieder',
        'Rennberichte'   => 'stories/category/rennberichte',
        'Trainingslager'   => 'stories/category/trainingslager',
        'Regionalliga'   => 'stories/category/regionalliga',
        'Landesliga'   => 'stories/category/landesliga',
        'Kinderschwimmen'   => 'stories/category/kinderschwimmen',
        'Jugendsparte'   => 'stories/category/jugendsparte',
    ];
    const NAME = 'Triabolos News';
    const URI = 'https://www.triabolos.de';
    const DESCRIPTION = 'News feed of Hamburg Triathlon club Triabolos';
    const MAINTAINER = 't3sec';
    const CACHE_TIMEOUT = 3600; // seconds
    const PARAMETERS    = [
        [
            'category' => [
                'name' => 'Triabolos news category',
                'type' => 'list',
                'values' => self::CATEGORIES,
                'defaultValue' => 'stories',
                'title' => 'Choose one of the available news categories',
            ],
        ],
    ];

    public function getURI()
    {
        if (!is_null($this->getInput('category'))) {
            return sprintf('%s/news/%s', static::URI, $this->getInput('category'));
        }

        return parent::getURI();
    }

    public function getDescription()
    {
        if (!is_null($this->getInput('category'))) {
            return sprintf('%s - %s', static::DESCRIPTION, array_search($this->getInput('category'), self::CATEGORIES));
        }

        return parent::getDescription();
    }

    public function getName()
    {
        if (!is_null($this->getInput('category'))) {
            return sprintf('%s - %s', static::NAME, array_search($this->getInput('category'), self::CATEGORIES));
        }

        return parent::getName();
    }

    public function collectData()
    {
        $dom = getSimpleHTMLDOMCached($this->getURI(), self::CACHE_TIMEOUT);
        foreach ($dom->find('.blog-listing .blog-item') as $li) {
            $a = $li->find('.blog-content .blog-header .blog-title a', 0);
            $time = $li->find('.blog-content .blog-header .blog-intro time', 0);
            $category = $li->find('.blog-content .blog-header .blog-intro .category-name a', 0);
            $content = $li->find('.blog-content .blog-text p', 0);
            $enclosure = $li->find('.img-blog a img', 0);
            $this->items[] = [
                'title' => $a->plaintext,
                'content' => $content->plaintext,
                'timestamp' => $time->datetime,
                'categories' => [$category->plaintext],
                'enclosure' => is_null($enclosure) ? [] : [self::URI . $enclosure->src],
                'uri' => self::URI . $a->href,
            ];
        }
    }
}