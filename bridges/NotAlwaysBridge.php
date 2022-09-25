<?php

class NotAlwaysBridge extends BridgeAbstract
{
    const MAINTAINER = 'mozes';
    const NAME = 'Not Always family Bridge';
    const URI = 'https://notalwaysright.com/';
    const DESCRIPTION = 'Returns the latest stories';
    const CACHE_TIMEOUT = 1800; // 30 minutes

    const PARAMETERS = [ [
                'filter' => [
                        'type' => 'list',
                        'name' => 'Filter',
                        'values' => [
                                'All' => '',
                                'Right' => 'right',
                                'Working' => 'working',
                                'Romantic' => 'romantic',
                                'Related' => 'related',
                                'Learning' => 'learning',
                                'Friendly' => 'friendly',
                                'Hopeless' => 'hopeless',
                                'Unfiltered' => 'unfiltered'
                        ]
                ]
        ]];

    public function getIcon()
    {
        return self::URI . 'favicon_nar.png';
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        foreach ($html->find('.post') as $post) {
            #print_r($post);
            $item = [];
            $item['uri'] = $post->find('h1', 0)->find('a', 0)->href;
            $item['content'] = $post;
            $item['title'] = $post->find('h1', 0)->find('a', 0)->innertext;
            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('filter'))) {
            return $this->getInput('filter') . ' - NotAlways Bridge';
        }

        return parent::getName();
    }

    public function getURI()
    {
        if (!is_null($this->getInput('filter'))) {
            return self::URI . $this->getInput('filter') . '/';
        }

        return parent::getURI();
    }
}
