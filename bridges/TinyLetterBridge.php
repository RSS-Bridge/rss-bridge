<?php

class TinyLetterBridge extends BridgeAbstract
{
    const NAME = 'Tiny Letter';
    const URI = 'https://tinyletter.com/';
    const DESCRIPTION = 'Tiny Letter is a mailing list service';
    const MAINTAINER = 'somini';
    const PARAMETERS = [
        [
            'username' => [
                'name' => 'User Name',
                'required' => true,
                'exampleValue' => 'forwards',
            ]
        ]
    ];

    public function getName()
    {
        $username = $this->getInput('username');
        if (!is_null($username)) {
            return static::NAME . ' | ' . $username;
        }

        return parent::getName();
    }

    public function getURI()
    {
        $username = $this->getInput('username');
        if (!is_null($username)) {
            return static::URI . urlencode($username);
        }

        return parent::getURI();
    }

    public function collectData()
    {
        $archives = $this->getURI() . '/archive';
        $html = getSimpleHTMLDOMCached($archives);

        foreach ($html->find('.message-list li') as $element) {
            $item = [];

            $snippet = $element->find('p.message-snippet', 0);
            $link = $element->find('.message-link', 0);

            $item['title'] = $link->plaintext;
            $item['content'] = $snippet->innertext;
            $item['uri'] = $link->href;
            $item['timestamp'] = strtotime($element->find('.message-date', 0)->plaintext);

            $this->items[] = $item;
        }
    }
}
