<?php

class MediapartBlogsBridge extends BridgeAbstract
{
    const NAME = 'Mediapart Blogs';
    const BASE_URI = 'https://blogs.mediapart.fr';
    const URI = self::BASE_URI . '/blogs';
    const MAINTAINER = 'somini';
    const PARAMETERS = [
        [
            'slug' => [
                'name' => 'Blog Slug',
                'type' => 'text',
                'title' => 'Blog user name',
                'required' => true,
                'exampleValue' => 'jean-vincot',
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://static.mediapart.fr/favicon/favicon-club.ico?v=2';
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::BASE_URI . '/' . $this->getInput('slug') . '/blog');

        foreach ($html->find('ul.post-list li') as $element) {
            $item = [];

            $item_title = $element->find('h3.title a', 0);
            $item_divs = $element->find('div');

            $item['title'] = $item_title->innertext;
            $item['uri'] = self::BASE_URI . trim($item_title->href);
            $item['author'] = $element->find('.author .subscriber', 0)->innertext;
            $item['content'] = $item_divs[count($item_divs) - 2] . $item_divs[count($item_divs) - 1];
            $item['timestamp'] = strtotime($element->find('.author time', 0)->datetime);

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if ($this->getInput('slug')) {
            return self::NAME . ' | ' . $this->getInput('slug');
        }
        return parent::getName();
    }
}
