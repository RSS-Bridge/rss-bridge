<?php

class FarsideNitterBridge extends FeedExpander
{
    const NAME = 'Farside Nitter Bridge';
    const DESCRIPTION = "Returns an user's recent tweets";
    const URI = 'https://farside.link/nitter/';
    const HOST = 'https://twitter.com/';
    const MAX_RETRIES = 3;
    const PARAMETERS = [
        [
            'username' => [
                'name' => 'username',
                'required' => true,
                'exampleValue' => 'NASA'
            ],
            'noreply' => [
                'name' => 'Without replies',
                'type' => 'checkbox',
                'title' => 'Only return initial tweets'
            ],
            'noretweet' => [
                'name' => 'Without retweets',
                'required' => false,
                'type' => 'checkbox',
                'title' => 'Hide retweets'
            ],
            'linkbacktotwitter' => [
                'name' => 'Link back to twitter',
                'required' => false,
                'type' => 'checkbox',
                'title' => 'Rewrite links back to twitter.com'
            ]
        ],
    ];

    public function detectParameters($url)
    {
        if (preg_match('/^(https?:\/\/)?(www\.)?(nitter\.net|twitter\.com)\/([^\/?\n]+)/', $url, $matches) > 0) {
            return [
                'username' => $matches[4],
                'noreply' => true,
                'noretweet' => true,
                'linkbacktotwitter' => true
            ];
        }
        return null;
    }

    public function collectData()
    {
        $this->getRSS();
    }

    private function getRSS($attempt = 0)
    {
        try {
            $this->collectExpandableDatas(self::URI . $this->getInput('username') . '/rss');
        } catch (\Exception $e) {
            if ($attempt >= self::MAX_RETRIES) {
                throw $e;
            } else {
                $this->getRSS($attempt++);
            }
        }
    }

    protected function parseItem(array $item)
    {
        if ($this->getInput('noreply') && substr($item['title'], 0, 5) == 'R to ') {
            return;
        }
        if ($this->getInput('noretweet') && substr($item['title'], 0, 6) == 'RT by ') {
            return;
        }
        $item['title'] = truncate($item['title']);
        if (preg_match('/(\/status\/.+)/', $item['uri'], $matches) > 0) {
            if ($this->getInput('linkbacktotwitter')) {
                $item['uri'] = self::HOST . $this->getInput('username') . $matches[1];
            } else {
                $item['uri'] = self::URI . $this->getInput('username') . $matches[1];
            }
        }
        return $item;
    }

    public function getName()
    {
        if (preg_match('/(.+) \//', parent::getName(), $matches) > 0) {
            return $matches[1];
        }
        return parent::getName();
    }

    public function getURI()
    {
        if ($this->getInput('linkbacktotwitter')) {
            return self::HOST . $this->getInput('username');
        } else {
            return self::URI . $this->getInput('username');
        }
    }
}
