<?php

declare(strict_types=1);

class NewgroundsBridge extends BridgeAbstract
{
    const NAME = 'Newgrounds';
    const URI = 'https://www.newgrounds.com';
    const DESCRIPTION = 'Get the latest art from a given user';
    const MAINTAINER = 'KamaleiZestri';
    const PARAMETERS = [
        'User' => [
            'username' => [
                'name' => 'Username',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'TomFulp'
            ]
        ]
    ];

    public function collectData()
    {
        $username = $this->getInput('username');
        if (!preg_match('/^\w+$/', $username)) {
            throw new \Exception('Illegal username');
        }

        $html = getSimpleHTMLDOM($this->getURI());

        $posts = $html->find('.item-portalitem-art-medium');

        foreach ($posts as $post) {
            $item = [];

            $item['author'] = $username;
            $item['uri'] = $post->href;

            $titleOrRestricted = $post->find('h4')[0]->innertext;

            // Newgrounds doesn't show public previews for NSFW content.
            if ($titleOrRestricted === 'Restricted Content: Sign in to view!') {
                $item['title'] = 'NSFW: ' . $item['uri'];
                $item['content'] = <<<EOD
<a href="{$item['uri']}">
{$item['title']}
</a>
EOD;
            } else {
                $item['title'] = $titleOrRestricted;
                $item['content'] = <<<EOD
<a href="{$item['uri']}">
<img
    style="align:top; width:270px; border:1px solid black;"
    alt="{$item['title']}"
    src="{$post->find('img')[0]->src}"
    title="{$item['title']}" />
</a>
EOD;
            }

            $this->items[] = $item;
        }
    }

    public function getName()
    {
        if ($this->getInput('username')) {
            return sprintf('%s - %s', $this->getInput('username'), self::NAME);
        }
        return parent::getName();
    }

    public function getURI()
    {
        if ($this->getInput('username')) {
            return sprintf('https://%s.newgrounds.com/art', $this->getInput('username'));
        }
        return parent::getURI();
    }
}
