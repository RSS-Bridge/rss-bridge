<?php

declare(strict_types=1);

class NewgroundsBridge extends BridgeAbstract
{
    const NAME = 'Newgrounds';
    const URI = 'https://www.newgrounds.com';
    const DESCRIPTION = 'Get the latest art from a given user';
    const MAINTAINER = 'KamaleiZestri';
    const CONFIGURATION = [
        'newgrounds_session' => [
            'required' => false,
            'defaultValue' => ''

        ],
        'ng_session' => [
            'required' => false,
            'defaultValue' => ''
        ]
    ];
    const PARAMETERS = [
        'User' => [
            'username' => [
                'name' => 'Username',
                'type' => 'text',
                'required' => true,
                'exampleValue' => 'TomFulp'
            ],
            'Nsfw' => [
                'name' => 'Nsfw',
                'type' => 'checkbox',
            ]
        ]
    ];
    /*
     * This was aquired by creating a new user on Newgrounds then
     * extracting the cookie from the browsers dev console.
     */
    private $NG_AUTH_COOKIE;

    public function collectData()
    {
        $this->NG_AUTH_COOKIE = /*'ng_user0=' . $this->getOption('ng_user0') . '; XSRF-TOKEN=' . $this->getOption('XSRF-TOKEN') .*/ 'newgrounds_session=' . $this->getOption('newgrounds_session') . '; ng_session=' . $this->getOption('ng_session');
        $username = $this->getInput('username');

        //I dont think this is needed? There are plenty of usernames that dont match this style.
        /*
        if (!preg_match('/^\w+$/', $username)) {
            throw new \Exception('Illegal username');
        }
        */

        $html = $this->postFASimpleHTMLDOM();

        $posts = $html->find('.item-portalitem-art-medium');

        foreach ($posts as $post) {
            $item = [];

            $item['author'] = $username;
            $item['uri'] = $post->href;

            $Restricted = $post->find('div')[3]->outertext;
            $title = $post->find('h4')[0]->innertext;
            //This kind of sucks but it works so
            if ($this->getInput('Nsfw') === false && $Restricted == '<div class="nohue-ngicon-small-rated-a" title="Rated A"></div>') {
                $item['title'] = 'NSFW: ' . $item['uri'];
                $item['content'] = <<<EOD
<a href="{$item['uri']}">
{$item['title']}
</a>
EOD;
            } else {
                $item['title'] = $title;
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

    public function getURIMODIFIED()
    {
        if ($this->getInput('username')) {
            return sprintf('https://%s.newgrounds.com', $this->getInput('username'));
        }
        return parent::getURIMODIFIED();
    }

    private function postFASimpleHTMLDOM()
    {
        $header = [
                'Host: ' . parse_url($this->getURIMODIFIED(), PHP_URL_HOST),
                'Cookie: ' . $this->NG_AUTH_COOKIE
            ];

        $html = getSimpleHTMLDOM($this->getURI(), $header);
        $html = defaultLinkTo($html, $this->getURI());
        return $html;
    }
}
