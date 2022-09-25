<?php

class RutubeBridge extends BridgeAbstract
{
    const NAME = 'Rutube';
    const URI = 'https://rutube.ru';
    const MAINTAINER = 'em92';
    const DESCRIPTION = 'Выводит ленту видео';

    const PARAMETERS = [
        'По каналу' => [
            'c' => [
                'name' => 'ИД канала',
                'exampleValue' => 1342940,  // Мятежник Джек
                'type' => 'number',
                'required' => true
            ],
        ],
        'По плейлисту' => [
            'p' => [
                'name' => 'ИД плейлиста',
                'exampleValue' => 83641,  // QRUSH
                'type' => 'number',
                'required' => true
            ],
        ],
    ];

    protected $title;

    public function getURI()
    {
        if ($this->getInput('c')) {
            return self::URI . '/channel/' . strval($this->getInput('c')) . '/videos/';
        } elseif ($this->getInput('p')) {
            return self::URI . '/plst/' . strval($this->getInput('p')) . '/';
        } else {
            return parent::getURI();
        }
    }

    public function getIcon()
    {
        return 'https://static.rutube.ru/static/favicon.ico';
    }

    public function getName()
    {
        if (is_null($this->title)) {
            return parent::getName();
        } else {
            return $this->title . ' - ' . parent::getName();
        }
    }

    private function getJSONData($html)
    {
        $jsonDataRegex = '/window.reduxState = (.*);/';
        preg_match($jsonDataRegex, $html, $matches) or returnServerError('Could not find reduxState');
        return json_decode(str_replace('\x', '\\\x', $matches[1]));
    }

    public function collectData()
    {
        $link = $this->getURI();

        $html = getContents($link);
        $reduxState = $this->getJSONData($html);
        $videos = [];
        if ($this->getInput('c')) {
            $videos = $reduxState->userChannel->videos->results;
            $this->title = $reduxState->userChannel->info->name;
        } elseif ($this->getInput('p')) {
            $videos = $reduxState->playlist->data->results;
            $this->title = $reduxState->playlist->title;
        }

        foreach ($videos as $video) {
            $item = new FeedItem();
            $item->setTitle($video->title);
            $item->setURI($video->video_url);
            $content = '<a href="' . $item->getURI() . '">';
            $content .= '<img src="' . $video->thumbnail_url . '" />';
            $content .= '</a><br/>';
            $content .= nl2br(
                // Converting links in plaintext
                // Copied from https://stackoverflow.com/a/12590772
                preg_replace(
                    '$(https?://[a-z0-9_./?=&#-]+)(?![^<>]*>)$i',
                    ' <a href="$1" target="_blank">$1</a> ',
                    $video->description . ' '
                )
            );
            $item->setTimestamp($video->created_ts);
            $item->setAuthor($video->author->name);
            $item->setContent($content);
            $this->items[] = $item;
        }
    }
}
