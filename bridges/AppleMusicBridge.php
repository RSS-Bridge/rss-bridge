<?php

class AppleMusicBridge extends BridgeAbstract
{
    const NAME = 'Apple Music';
    const URI = 'https://www.apple.com';
    const DESCRIPTION = 'Fetches the latest releases from an artist';
    const MAINTAINER = 'bockiii';
    const PARAMETERS = [[
        'artist' => [
            'name' => 'Artist ID',
            'exampleValue' => '909253',
            'required' => true,
        ],
        'limit' => [
            'name' => 'Latest X Releases (max 50)',
            'defaultValue' => '10',
            'required' => true,
        ],
    ]];
    const CACHE_TIMEOUT = 60 * 60 * 6; // 6 hours

    public function collectData()
    {
        $json = $this->getBasics();
        $artist = $this->getArtist($json);

        foreach ($json as $obj) {
            if ($obj->wrapperType === 'collection') {
                $copyright = $obj->copyright ?? '';
                $artworkUrl500 = str_replace('/100x100', '/500x500', $obj->artworkUrl100);
                $artworkUrl2000 = str_replace('/100x100', '/2000x2000', $obj->artworkUrl100);

                $this->items[] = [
                    'title' => $obj->collectionName,
                    'uri' => $obj->collectionViewUrl,
                    'timestamp' => $obj->releaseDate,
                    'enclosures' => $artworkUrl500,
                    'author' => $obj->artistName,
                    'content' => '<figure>'
                        . '<img'
                        . ' srcset="'
                        . $obj->artworkUrl60 . ' 60w'
                        . ', ' . $obj->artworkUrl100 . ' 100w'
                        . ', ' . $artworkUrl500 . ' 500w'
                        . ', ' . $artworkUrl2000 . ' 2000w"'
                        . ' sizes="100%"'
                        . ' src="' . $artworkUrl2000 . '"'
                        . ' alt="Cover of ' . str_replace("\"", "\\\"", $obj->collectionName) . '"'
                        . ' style="display: block; margin: 0 auto;" />'
                        . '<figcaption>'
                        . 'from <a href="' . $artist->artistLinkUrl . '">' . $obj->artistName . '</a><br />'
                        . $copyright
                        . '</figcaption>'
                        . '</figure>',
                ];
            }
        }
    }

    private function getBasics()
    {
        # Limit the amount of releases to 50
        if ($this->getInput('limit') > 50) {
            $limit = 50;
        } else {
            $limit = $this->getInput('limit');
        }

        $url = 'https://itunes.apple.com/lookup?id=' . $this->getInput('artist') . '&entity=album&limit=' . $limit . '&sort=recent';
        $html = getSimpleHTMLDOM($url);

        $json = json_decode($html);
        $result = $json->results;

        if (!is_array($result) || count($result) == 0) {
            returnServerError('There is no artist with id "' . $this->getInput('artist') . '".');
        }

        return $result;
    }

    private function getArtist($json)
    {
        $nameArray = array_filter($json, function ($obj) {
            return $obj->wrapperType == 'artist';
        });

        if (count($nameArray) === 1) {
            return $nameArray[0];
        }

        return parent::getName();
    }

    public function getName()
    {
        if (empty($this->getInput('artist'))) {
            return parent::getName();
        }

        $json = $this->getBasics();

        return $this->getArtist($json)->artistName;
    }

    public function getIcon()
    {
        if (empty($this->getInput('artist'))) {
            return parent::getIcon();
        }

        // it isn't necessary to set the correct artist name into the url
        $url = 'https://music.apple.com/us/artist/jon-bellion/' . $this->getInput('artist');
        $html = getSimpleHTMLDOMCached($url);
        $image = $html->find('meta[property="og:image"]', 0)->content;

        $imageHighResolution = preg_replace('/\/\d*x\d*cw/i', '/144x144-999', $image);

        return $imageHighResolution;
    }
}
