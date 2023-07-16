<?php

class TraktBridge extends BridgeAbstract
{
    const NAME = 'Trakt Bridge';
    const DESCRIPTION = "Returns a user's watch history";
    const URI = 'https://www.trakt.tv';

    const PARAMETERS = [
        [
            'username' => [
                'name' => 'username',
                'required' => true
            ],
            'hide_shows' => [
                'name' => 'Hide shows',
                'type' => 'checkbox',
                'title' => 'Hide shows',
            ],

        ],
    ];

    public function detectParameters($url)
    {
        if (preg_match('/trakt\.tv\/users\/(.*?)\//', $url, $matches) > 0) {
            return [
                'username' => $matches[1]
            ];
        }
        return null;
    }

    public function collectData()
    {
        $username = $this->getInput('username');
        $dom = getSimpleHTMLDOMCached(self::URI . '/users/' . $username . '/history');
        $this->feedName = $dom->find('#avatar-wrapper h1 a', 0)->plaintext;
        $this->iconURL = $dom->find('img.avatar', 0)->{'src'};

        foreach ($dom->find('#history-items .posters', 0)->find('div.grid-item') as $div) {
            if ($this->getInput('hide_shows') && $div->{'data-type'} != 'movie') {
                continue;
            }
            $item = [];
            $item['author'] = $this->feedName;
            $item['title'] = $div->find('img.real', 0)->{'title'};
            $item['timestamp'] = $div->find('.format-date', 0)->plaintext;
            $item['content'] = '<img src="' . $div->find('img.real', 0)->{'data-original'} . '">';
            $item['uri'] = self::URI . $div->{'data-url'};
            $this->items[] = $item;
        }
    }
    public function getName()
    {
        if (empty($this->feedName)) {
            return parent::getName();
        } else {
            return $this->feedName;
        }
    }
    public function getIcon()
    {
        if (empty($this->iconURL)) {
            return parent::getIcon();
        } else {
            return $this->iconURL;
        }
    }
}
