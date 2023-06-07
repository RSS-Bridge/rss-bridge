<?php

class RemixAudioBridge extends BridgeAbstract
{
    const MAINTAINER = 'Simounet';
    const NAME = 'RemixAudio';
    const URI = 'https://remix.audio';
    const CACHE_TIMEOUT = 0; //6h
    //const CACHE_TIMEOUT = 21600; //6h
    const DESCRIPTION = 'RemixAudio profiles';
    const PROFILE_QUERY_PARAM = 'profile';

    const PARAMETERS = [
        [
            self::PROFILE_QUERY_PARAM => [
                'name' => 'Profile',
                'type' => 'text',
                'exampleValue' => 'Amoraboy',
                'required' => true
            ]
        ]
    ];

    private $feedTitle = null;
    private $feedIcon = null;

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $user = $this->getUser($html);

        $this->feedTitle = $user['name'];
        $this->feedIcon = $user['avatar'];

        $elements = $html->find('.song-container');
        foreach ($elements as $element) {
            $titleEl = $element->find('.song-title', 0);
            $title = is_object($titleEl) ? $titleEl->plaintext : '';

            $urlEl = $titleEl->find('a', 0);

            $publishedEl = $element->find('.timeago', 0);

            $songEl = $element->find('.song-play-btn', 0);
            $song = is_object($songEl) ? '<audio controls><source src="' . $songEl->getAttribute('data-track-url') . '" type="audio/mpeg" /></audio>' : '';

            $item = [];
            $item['uri'] = $urlEl->href;
            $item['title'] = $title;
            $item['timestamp'] = strtotime($publishedEl->title);
            $item['content'] = '<p>' . $user['name'] . ' - ' . $title . '</p>' . $song;

            $this->items[] = $item;
        }
    }

    public function getIcon()
    {
        if ($this->feedIcon) {
            return $this->feedIcon;
        }

        return parent::getIcon();
    }

    public function getName()
    {
        if ($this->feedTitle) {
            return $this->feedTitle . ' - ' . self::NAME;
        }

        return parent::getName();
    }

    public function getURI()
    {
        $profile = $this->getProfile();
        if ($profile) {
            return self::URI . '/profile/' . $profile;
        }

        return parent::getURI();
    }

    private function getUser($html)
    {
        return [
            'avatar' => $html->find('.cover-avatar img', 0)->src,
            'name' => $html->find('.cover-username a', 0)->plaintext
        ];
    }

    private function getProfile()
    {
        return $this->getInput(self::PROFILE_QUERY_PARAM);
    }
}
