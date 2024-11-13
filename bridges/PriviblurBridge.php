<?php

class PriviblurBridge extends BridgeAbstract
{
    const NAME = 'Priviblur';
    const MAINTAINER = 'phantop';
    const URI = 'https://github.com/syeopite/priviblur';
    const DESCRIPTION = 'Returns Tumblr posts from a Priviblur link';
    const PARAMETERS = [
        [
            'url' => [
                'name' => 'URL',
                'exampleValue' => 'https://priviblur.fly.dev',
                'required' => true,
            ]
        ]
    ];

    private $title;
    private $favicon = 'https://www.tumblr.com/favicon.ico';

    public function collectData()
    {
        $url = $this->getURI();
        $html = getSimpleHTMLDOM($url);
        $html = defaultLinkTo($html, $url);
        $this->title = $html->find('head title', 0)->innertext;

        if ($html->find('#blog-header img.avatar', 0)) {
            $icon = $html->find('#blog-header img.avatar', 0)->src;
            $this->favicon = str_replace('pnj', 'png', $icon);
        }

        $elements = $html->find('.post');
        foreach ($elements as $element) {
            $item = [];
            $item['author'] = $element->find('.primary-post-author .blog-name', 0)->innertext;
            $item['comments'] = $element->find('.interaction-buttons > a', 1)->href;
            $item['content'] = $element->find('.post-body', 0);
            $item['timestamp'] = $element->find('.primary-post-author time', 0)->innertext;
            $item['title'] = $item['author'] . ': ' . $item['timestamp'];
            $item['uid'] = $item['comments']; // tumblr url is canonical
            $item['uri'] = $element->find('.interaction-buttons > a', 0)->href;

            if ($element->find('.post-tags', 0)) {
                $tags = html_entity_decode($element->find('.post-tags', 0)->plaintext);
                $tags = explode('#', $tags);
                $tags = array_map('trim', $tags);
                array_shift($tags);
                $item['categories'] = $tags;
            }

            $heading = $element->find('h1', 0);
            if ($heading) {
                $item['title'] = $heading->innertext;
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        $name = parent::getName();
        if (isset($this->title)) {
            $name = $this->title;
        }
        return $name;
    }

    public function getURI()
    {
        return $this->getInput('url') ?? parent::getURI();
    }

    public function getIcon()
    {
        return $this->favicon;
    }
}
