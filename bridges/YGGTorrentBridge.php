<?php

/* This is a mashup of FlickrExploreBridge by sebsauvage and FlickrTagBridge
 * by erwang.providing the functionality of both in one.
 */
class YGGTorrentBridge extends BridgeAbstract
{
    const MAINTAINER = 'teromene';
    const NAME = 'Yggtorrent Bridge';
    const URI = 'https://www3.yggtorrent.qa';
    const DESCRIPTION = 'Returns torrent search from Yggtorrent';

    const PARAMETERS = [
        [
            'cat' => [
                'name' => 'category',
                'type' => 'list',
                'values' => [
                    'Toutes les catégories' => 'all.all',
                    'Film/Vidéo - Toutes les sous-catégories' => '2145.all',
                    'Film/Vidéo - Animation' => '2145.2178',
                    'Film/Vidéo - Animation Série' => '2145.2179',
                    'Film/Vidéo - Concert' => '2145.2180',
                    'Film/Vidéo - Documentaire' => '2145.2181',
                    'Film/Vidéo - Émission TV' => '2145.2182',
                    'Film/Vidéo - Film' => '2145.2183',
                    'Film/Vidéo - Série TV' => '2145.2184',
                    'Film/Vidéo - Spectacle' => '2145.2185',
                    'Film/Vidéo - Sport' => '2145.2186',
                    'Film/Vidéo - Vidéo-clips' => '2145.2186',
                    'Audio - Toutes les sous-catégories' => '2139.all',
                    'Audio - Karaoké' => '2139.2147',
                    'Audio - Musique' => '2139.2148',
                    'Audio - Podcast Radio' => '2139.2150',
                    'Audio - Samples' => '2139.2149',
                    'Jeu vidéo - Toutes les sous-catégories' => '2142.all',
                    'Jeu vidéo - Autre' => '2142.2167',
                    'Jeu vidéo - Linux' => '2142.2159',
                    'Jeu vidéo - MacOS' => '2142.2160',
                    'Jeu vidéo - Microsoft' => '2142.2162',
                    'Jeu vidéo - Nintendo' => '2142.2163',
                    'Jeu vidéo - Smartphone' => '2142.2165',
                    'Jeu vidéo - Sony' => '2142.2164',
                    'Jeu vidéo - Tablette' => '2142.2166',
                    'Jeu vidéo - Windows' => '2142.2161',
                    'eBook - Toutes les sous-catégories' => '2140.all',
                    'eBook - Audio' => '2140.2151',
                    'eBook - Bds' => '2140.2152',
                    'eBook - Comics' => '2140.2153',
                    'eBook - Livres' => '2140.2154',
                    'eBook - Mangas' => '2140.2155',
                    'eBook - Presse' => '2140.2156',
                    'Emulation - Toutes les sous-catégories' => '2141.all',
                    'Emulation - Emulateurs' => '2141.2157',
                    'Emulation - Roms' => '2141.2158',
                    'GPS - Toutes les sous-catégories' => '2141.all',
                    'GPS - Applications' => '2141.2168',
                    'GPS - Cartes' => '2141.2169',
                    'GPS - Divers' => '2141.2170'
                ]
            ],
            'nom' => [
                'name' => 'Nom',
                'description' => 'Nom du torrent',
                'type' => 'text',
                'exampleValue' => 'matrix'
            ],
            'description' => [
                'name' => 'Description',
                'description' => 'Description du torrent',
                'type' => 'text'
            ],
            'fichier' => [
                'name' => 'Fichier',
                'description' => 'Fichier du torrent',
                'type' => 'text'
            ],
            'uploader' => [
                'name' => 'Uploader',
                'description' => 'Uploader du torrent',
                'type' => 'text'
            ],

        ]
    ];

    public function collectData()
    {
        $catInfo = explode('.', $this->getInput('cat'));
        $category = $catInfo[0];
        $subcategory = $catInfo[1];

        $html = getSimpleHTMLDOM(self::URI . '/engine/search?name='
                    . $this->getInput('nom')
                    . '&description='
                    . $this->getInput('description')
                    . '&file='
                    . $this->getInput('fichier')
                    . '&uploader='
                    . $this->getInput('uploader')
                    . '&category='
                    . $category
                    . '&sub_category='
                    . $subcategory
                    . '&do=search&order=desc&sort=publish_date');

        $count = 0;
        $results = $html->find('.results', 0);
        if (!$results) {
            return;
        }

        foreach ($results->find('tr') as $row) {
            $count++;
            if ($count == 1) {
                continue; // Skip table header
            }
            if ($count == 22) {
                break; // Stop processing after 21 items (20 + 1 table header)
            }
            $item = [];
            $item['timestamp'] = $row->find('.hidden', 1)->plaintext;
            $item['title'] = $row->find('a#torrent_name', 0)->plaintext;
            $item['uri'] = $this->processLink($row->find('a#torrent_name', 0)->href);
            $item['seeders'] = $row->find('td', 7)->plaintext;
            $item['leechers'] = $row->find('td', 8)->plaintext;
            $item['size'] = $row->find('td', 5)->plaintext;
            $item = array_merge($item, $this->collectTorrentData($item['uri']));

            $this->items[] = $item;
        }
    }

    /**
     * Convert special characters like é to %C3%A9 in the url
     */
    private function processLink($url)
    {
        $url = explode('/', $url);
        foreach ($url as $index => $value) {
            // Skip https://{self::URI}/
            if ($index < 3) {
                continue;
            }
            // Decode first so that characters like + are not encoded
            $url[$index] = urlencode(urldecode($value));
        }
        return implode('/', $url);
    }

    private function collectTorrentData($url)
    {
        $page = defaultLinkTo(getSimpleHTMLDOMCached($url), self::URI);
        $author = $page->find('.informations tr', 5)->find('td', 1)->plaintext;
        $content = $page->find('.default', 1);
        return ['author' => $author, 'content' => $content];
    }
}
