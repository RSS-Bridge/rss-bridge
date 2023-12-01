<?php

declare(strict_types=1);

class EZTVBridge extends BridgeAbstract
{
    const MAINTAINER = 'alexAubin';
    const NAME = 'EZTV';
    const URI = 'https://eztvstatus.com';
    const DESCRIPTION = 'Search for torrents by IMDB id. You can find IMDB id in the url of a tv show.';

    const PARAMETERS = [
        [
            'ids' => [
                'name' => 'IMDB ids',
                'exampleValue' => '8740790,1733785',
                'required' => true,
                'title' => 'One or more IMDB ids'
            ],
            'no480' => [
                'name' => 'No 480p',
                'type' => 'checkbox',
                'title' => 'Activate to exclude 480p torrents'
            ],
            'no720' => [
                'name' => 'No 720p',
                'type' => 'checkbox',
                'title' => 'Activate to exclude 720p torrents'
            ],
            'no1080' => [
                'name' => 'No 1080p',
                'type' => 'checkbox',
                'title' => 'Activate to exclude 1080p torrents'
            ],
            'no2160' => [
                'name' => 'No 2160p',
                'type' => 'checkbox',
                'title' => 'Activate to exclude 2160p torrents'
            ],
            'noUnknownRes' => [
                'name' => 'No Unknown resolution',
                'type' => 'checkbox',
                'title' => 'Activate to exclude unknown resolution torrents'
            ],
        ]
    ];

    public function collectData()
    {
        $eztv_uri = $this->getEztvUri();
        $ids = explode(',', trim($this->getInput('ids')));
        foreach ($ids as $id) {
            $data = json_decode(getContents(sprintf('%s/api/get-torrents?imdb_id=%s', $eztv_uri, $id)));
            if (!isset($data->torrents)) {
                // No results
                continue;
            }
            foreach ($data->torrents as $torrent) {
                $title = $torrent->title;
                $regex480 = '/480p/';
                $regex720 = '/720p/';
                $regex1080 = '/1080p/';
                $regex2160 = '/2160p/';
                $regexUnknown = '/(480p|720p|1080p|2160p)/';
                // Skip unwanted resolution torrents
                if (
                    (preg_match($regex480, $title) === 1 && $this->getInput('no480'))
                    || (preg_match($regex720, $title) === 1 && $this->getInput('no720'))
                    || (preg_match($regex1080, $title) === 1 && $this->getInput('no1080'))
                    || (preg_match($regex2160, $title) === 1 && $this->getInput('no2160'))
                    || (preg_match($regexUnknown, $title) !== 1 && $this->getInput('noUnknownRes'))
                ) {
                    continue;
                }
                $this->items[] = $this->getItemFromTorrent($torrent);
            }
        }
        usort($this->items, function ($torrent1, $torrent2) {
            return $torrent2['timestamp'] <=> $torrent1['timestamp'];
        });
    }

    protected function getEztvUri()
    {
        $html = getSimpleHTMLDom(self::URI);
        $urls = $html->find('a.domainLink');
        foreach ($urls as $url) {
            $headers = get_headers($url->href);
            if (substr($headers[0], 9, 3) === '200') {
                return $url->href;
            }
        }
        throw new Exception('No valid EZTV URI available');
    }

    protected function getItemFromTorrent($torrent)
    {
        $item = [];
        $item['uri'] = $torrent->episode_url;
        $item['author'] = $torrent->imdb_id;
        $item['timestamp'] = $torrent->date_released_unix;
        $item['title'] = $torrent->title;
        $item['enclosures'][] = $torrent->torrent_url;

        $thumbnailUri = 'https:' . $torrent->small_screenshot;
        $torrentSize = format_bytes((int) $torrent->size_bytes);

        $item['content'] = $torrent->filename . '<br>File size: '
        . $torrentSize . '<br><a href="' . $torrent->magnet_url
        . '">magnet link</a><br><a href="' . $torrent->torrent_url
        . '">torrent link</a><br><img src="' . $thumbnailUri . '" />';

        return $item;
    }
}
