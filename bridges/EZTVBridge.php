<?php

declare(strict_types=1);

/**
 * api docs: https://eztv1.xyz/api/
 */
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
            $url = sprintf('%s/api/get-torrents?imdb_id=%s', $eztv_uri, $id);
            $json = getContents($url);
            $data = json_decode($json);
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
        $episode_url = $torrent->episode_url ?? null;
        $torrent_url = $torrent->torrent_url ?? null;
        $magnet_url = $torrent->magnet_url ?? null;

        $item = [];

        $item['uri'] = $episode_url ?? $torrent_url;
        $item['author'] = $torrent->imdb_id;
        $item['timestamp'] = $torrent->date_released_unix;
        $item['title'] = $torrent->title;

        $thumbnailUri = 'https:' . $torrent->small_screenshot;
        $torrentSize = format_bytes((int) $torrent->size_bytes);

        $content = $torrent->filename . '<br>File size: ' . $torrentSize;

        if ($magnet_url) {
            $content .= '<br><a href="' . $magnet_url . '">magnet link</a>';
        }

        if ($torrent_url) {
            $item['enclosures'][] = $torrent_url;
            $content .= '<br><a href="' . $torrent_url . '">torrent link</a>';
        }

        $content .= '<br><img src="' . $thumbnailUri . '" />';

        $item['content'] = $content;

        return $item;
    }
}
