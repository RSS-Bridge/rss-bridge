<?php

class PornhubBridge extends BridgeAbstract
{
    const MAINTAINER = 'Mitsukarenai';
    const NAME = 'Pornhub';
    const URI = 'https://www.pornhub.com/';
    const CACHE_TIMEOUT = 3600; // 1h
    const DESCRIPTION = 'Returns videos from specified user,model,pornstar';

    const PARAMETERS = [[
        'q' => [
            'name' => 'User name',
            'exampleValue' => 'asa-akira',
            'required' => true,
        ],
        'type' => [
            'name' => 'User type',
            'type' => 'list',
            'values' => [
                'user' => 'users',
                'model' => 'model',
                'pornstar' => 'pornstar',
            ],
            'defaultValue' => 'pornstar',
        ],
        'sort' => [
            'name' => 'Sort by',
            'type' => 'list',
            'values' => [
                'Most recent' => '?',
                'Most views' => '?o=mv',
                'Top rated' => '?o=tr',
                'Longest' => '?o=lg',
            ],
            'defaultValue' => '?',
        ],
        'show_images' => [
            'name' => 'Show thumbnails',
            'type' => 'checkbox',
        ],
    ]];

    public function getName()
    {
        if (!is_null($this->getInput('type')) && !is_null($this->getInput('q'))) {
            return 'PornHub ' . $this->getInput('type') . ':' . $this->getInput('q');
        }

        return parent::getName();
    }

    public function collectData()
    {
        $uri = 'https://www.pornhub.com/' . $this->getInput('type') . '/';
        switch ($this->getInput('type')) {   // select proper permalink format per user type...
            case 'model':
                $uri .= urlencode($this->getInput('q')) . '/videos' . $this->getInput('sort');
                break;
            case 'users':
                $uri .= urlencode($this->getInput('q')) . '/videos/public' . $this->getInput('sort');
                break;
            case 'pornstar':
                $uri .= urlencode($this->getInput('q')) . '/videos/upload' . $this->getInput('sort');
                break;
        }

        $show_images = $this->getInput('show_images');

        $html = getSimpleHTMLDOM($uri, [
            'cookie: accessAgeDisclaimerPH=1'
        ]);

        foreach ($html->find('div.videoUList ul.videos li.videoblock') as $element) {
            $item = [];

            $item['author'] = $this->getInput('q');

            // Title
            $title = $element->find('a', 0)->getAttribute('title');
            if (is_null($title)) {
                continue;
            }
            $item['title'] = $title;

            // Url
            $url = $element->find('a', 0)->href;
            $item['uri'] = 'https://www.pornhub.com' . $url;

            // Duration
            $marker = $element->find('div.marker-overlays var', 0);
            $duration = $marker->innertext ?? '';

            // Content
            $videoImage = $element->find('img', 0);
            $image = $videoImage->getAttribute('data-src') ?: $videoImage->getAttribute('src');
            if ($show_images === true) {
                $item['content'] = sprintf('<a href="%s"><img src="%s"></a><br>%s', $item['uri'], $image, $duration);
            }

            $uploaded = explode('/', $image);
            if (isset($uploaded[4])) {
                // date hack, guess upload YYYYMMDD from thumbnail URL (format: https://ci.phncdn.com/videos/201907/25/--- )
                $uploadTimestamp = strtotime($uploaded[4] . $uploaded[5]);
                $item['timestamp'] = $uploadTimestamp;
            } else {
                // The thumbnail url did not have a date in it for some unknown reason
            }
            $this->items[] = $item;
        }
    }
}
