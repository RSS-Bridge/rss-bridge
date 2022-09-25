<?php

class IKWYDBridge extends BridgeAbstract
{
    const MAINTAINER = 'DevonHess';
    const NAME = 'I Know What You Download';
    const URI = 'https://iknowwhatyoudownload.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns torrent downloads and distributions for an IP address';
    const PARAMETERS = [
        [
            'ip' => [
                'name' => 'IP Address',
                'exampleValue' => '8.8.8.8',
                'required' => true
            ],
            'update' => [
                'name' => 'Update last seen',
                'type' => 'checkbox',
                'title' => 'Update timestamp every time "last seen" changes'
            ]
        ]
    ];
    private $name;
    private $uri;

    public function detectParameters($url)
    {
        $params = [];

        $regex = '/^(https?:\/\/)?iknowwhatyoudownload\.com\/';
        $regex .= '(?:en|ru)\/peer\/\?ip=(\d+\.\d+\.\d+\.\d+)/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['ip'] = urldecode($matches[2]);
            return $params;
        }

        $regex = '/^(https?:\/\/)?iknowwhatyoudownload\.com\/';
        $regex .= '(?:(?:en|ru)\/peer\/)?/';
        if (preg_match($regex, $url, $matches) > 0) {
            $params['ip'] = $_SERVER['REMOTE_ADDR'];
            return $params;
        }

        return null;
    }

    public function getName()
    {
        if ($this->name) {
            return $this->name;
        } else {
            return self::NAME;
        }
    }

    public function getURI()
    {
        if ($this->uri) {
            return $this->uri;
        } else {
            return self::URI;
        }
    }

    public function collectData()
    {
        $ip = $this->getInput('ip');
        $root = self::URI . 'en/peer/?ip=' . $ip;
        $html = getSimpleHTMLDOM($root);

        $this->name = 'IKWYD: ' . $ip;
        $this->uri = $root;

        foreach ($html->find('.table > tbody > tr') as $download) {
            $download = defaultLinkTo($download, self::URI);
            $firstSeen = $download->find(
                '.date-column',
                0
            )->innertext;
            $lastSeen = $download->find(
                '.date-column',
                1
            )->innertext;
            $category = $download->find(
                '.category-column',
                0
            )->innertext;
            $torlink = $download->find(
                '.name-column > div > a',
                0
            );
            $tortitle = strip_tags($torlink);
            $size = $download->find('td', 4)->innertext;
            $title = $tortitle;
            $author = $ip;

            if ($this->getInput('update')) {
                $timestamp = strtotime($lastSeen);
            } else {
                $timestamp = strtotime($firstSeen);
            }

            $uri = $torlink->href;

            $content = 'IP address: <a href="' . $root . '">';
            $content .= $ip . '</a><br>';
            $content .= 'First seen: ' . $firstSeen . '<br>';
            $content .= ($this->getInput('update') ? 'Last seen: ' .
                $lastSeen . '<br>' : '');
            $content .= ($category ? 'Category: ' .
                $category . '<br>' : '');
            $content .= 'Title: ' . $torlink . '<br>';
            $content .= 'Size: ' . $size;

            $item = [];
            $item['uri'] = $uri;
            $item['title'] = $title;
            $item['author'] = $author;
            $item['timestamp'] = $timestamp;
            $item['content'] = $content;
            if ($category) {
                $item['categories'] = [$category];
            }
            $this->items[] = $item;
        }
    }
}
