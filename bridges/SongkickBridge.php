<?php

class SongkickBridge extends BridgeAbstract
{
    const NAME = 'Songkick';
    const URI = 'https://songkick.com/';
    const DESCRIPTION = 'Fetches the concerts of an artist';
    const MAINTAINER = 'joaomqc';
    const CACHE_TIMEOUT = 3600;
    const PARAMETERS = [ [
        'artistid' => [
            'name' => 'Artist ID',
            'type' => 'text',
            'required' => true,
            'exampleValue' => '2506696-imagine-dragons',
        ]
    ] ];

    const ARTIST_URI = 'https://www.songkick.com/artists/%s/';
    const CALENDAR_URI = self::ARTIST_URI . 'calendar';

    private $name = '';

    public function getURI()
    {
        return sprintf(self::ARTIST_URI, $this->getInput('artistid'));
    }

    public function getName()
    {
        if (!empty($this->name)) {
            return $this->name . ' - ' . parent::getName();
        }
        return parent::getName();
    }

    public function getIcon()
    {
        return 'https://assets.sk-static.com/images/nw/furniture/songkick-logo.svg';
    }

    public function collectData()
    {
        $url = sprintf(self::CALENDAR_URI, $this->getInput('artistid'));

        $dom = getSimpleHTMLDOM($url);

        $jsonscript = $dom->find('div.microformat > script', 0);

        if (empty($this->name) && $jsonscript) {
            $this->name = json_decode($jsonscript->innertext)[0]->name;
        }

        $dom = $dom->find('div.container > div.row > div.primary', 0);

        if (!$dom) {
            throw new Exception(sprintf('Unable to find css selector on `%s`', $url));
        }
        $dom = defaultLinkTo($dom, $this->getURI());

        foreach ($dom->find('div[@id="calendar-summary"] > ol > li') as $article) {
            $detailsobj = json_decode($article->find('div.microformat > script', 0)->innertext)[0];

            $a = $article->find('a', 0);

            $details = $a->find('div.event-details', 0);
            $title = $details->find('.secondary-detail', 0)->plaintext;
            $city = $details->find('.primary-detail', 0)->plaintext;
            $event = $detailsobj->location->name;

            $content = 'City: ' . $city . '<br>Event: ' . $event . '<br>Date: ' . $article->title;

            $categories = [];
            if ($details->hasClass('concert')) {
                $categories[] = 'concert';
            }
            if ($details->hasClass('festival')) {
                $categories[] = 'festival';
            }
            if (!is_null($details->find('.outdoor', 0))) {
                $categories[] = 'outdoor';
            }

            $this->items[] = [
                'title' => $title,
                'uri' => $a->href,
                'content' => $content,
                'categories' => $categories,
            ];
        }
    }
}
