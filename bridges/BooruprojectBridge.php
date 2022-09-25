<?php

class BooruprojectBridge extends DanbooruBridge
{
    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Booruproject';
    const URI = 'https://booru.org/';
    const DESCRIPTION = 'Returns images from given page of booruproject';
    const PARAMETERS = [
        'global' => [
            'p' => [
                'name' => 'page',
                'defaultValue' => 0,
                'type' => 'number'
            ],
            't' => [
                'name' => 'tags',
                'required' => true,
                'exampleValue'  => 'tagme',
                'title' => 'Use "all" to get all posts'
            ]
        ],
        'Booru subdomain (subdomain.booru.org)' => [
            'i' => [
                'name' => 'Subdomain',
                'required' => true,
                'exampleValue'  => 'rm'
            ]
        ]
    ];

    const PATHTODATA = '.thumb';
    const IDATTRIBUTE = 'id';
    const TAGATTRIBUTE = 'title';
    const PIDBYPAGE = 20;

    protected function getFullURI()
    {
        return $this->getURI()
        . 'index.php?page=post&s=list&pid='
        . ($this->getInput('p') ? ($this->getInput('p') - 1) * static::PIDBYPAGE : '')
        . '&tags=' . urlencode($this->getInput('t'));
    }

    protected function getTags($element)
    {
        $tags = parent::getTags($element);
        $tags = explode(' ', $tags);

        // Remove statistics from the tags list (identified by colon)
        foreach ($tags as $key => $tag) {
            if (strpos($tag, ':') !== false) {
                unset($tags[$key]);
            }
        }

        return implode(' ', $tags);
    }

    public function getURI()
    {
        if (!is_null($this->getInput('i'))) {
            return 'https://' . $this->getInput('i') . '.booru.org/';
        }

        return parent::getURI();
    }

    public function getName()
    {
        if (!is_null($this->getInput('i'))) {
            return static::NAME . ' ' . $this->getInput('i');
        }

        return parent::getName();
    }
}
