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

    private $title;

    public function collectData()
    {
        $items = $this->getJson();
        $artist = $this->getArtist($items);

        $this->title = $artist->artistName;

        foreach ($items as $item) {
            if ($item->wrapperType === 'collection') {
                $copyright = $item->copyright ?? '';
                $artworkUrl500 = str_replace('/100x100', '/500x500', $item->artworkUrl100);
                $artworkUrl2000 = str_replace('/100x100', '/2000x2000', $item->artworkUrl100);
                $escapedCollectionName = htmlspecialchars($item->collectionName);

                $this->items[] = [
                    'title' => $item->collectionName,
                    'uri' => $item->collectionViewUrl,
                    'timestamp' => $item->releaseDate,
                    'enclosures' => $artworkUrl500,
                    'author' => $item->artistName,
                    'content' => "<figure>
    <img srcset=\"$item->artworkUrl60 60w, $item->artworkUrl100 100w, $artworkUrl500 500w, $artworkUrl2000 2000w\"
         sizes=\"100%\" src=\"$artworkUrl2000\"
         alt=\"Cover of $escapedCollectionName\"
         style=\"display: block; margin: 0 auto;\" />
    <figcaption>
        from <a href=\"$artist->artistLinkUrl\">$item->artistName</a><br />$copyright
    </figcaption>
</figure>",
                ];
            }
        }
    }

    private function getJson()
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
        if (isset($this->title)) {
            return $this->title;
        }

        return parent::getName();
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

        $imageUpdatedSize = preg_replace('/\/\d*x\d*cw/i', '/144x144-999', $image);

        return $imageUpdatedSize;
    }
}
