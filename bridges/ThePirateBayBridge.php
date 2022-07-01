<?php

/**
 * Much of the logic here is copied from https://thepiratebay.org/static/main.js
 */
class ThePirateBayBridge extends BridgeAbstract
{
    const MAINTAINER = 'dvikan';
    const NAME = 'The Pirate Bay';
    const URI = 'https://thepiratebay.org';
    const DESCRIPTION = 'Returns results for the keywords. You can put several
 list of keywords by separating them with a semicolon (e.g. "one show;another
 show"). Category based search needs the category number as input. User based
 search takes the Uploader name. Search can be done in a specified category';

    const PARAMETERS = [ [
        'q' => [
            'name' => 'keywords/username/category, separated by semicolons',
            'exampleValue' => 'simpsons',
            'required' => true
        ],
        'crit' => [
            'type' => 'list',
            'name' => 'Search type',
            'values' => [
                'search' => 'search',
                'category' => 'cat',
                'user' => 'usr',
            ]
        ],
        'catCheck' => [
            'type' => 'checkbox',
            'name' => 'Specify category for keyword search ?',
        ],
        'cat' => [
            'name' => 'Category number',
            'exampleValue' => '100, 200… See TPB for category number'
        ],
        'trusted' => [
            'type' => 'checkbox',
            'name' => 'Only get results from Trusted or VIP users ?',
        ],
    ]];

    const STATIC_SERVER = 'https://torrindex.net';

    const CATEGORIES = [
        '1' => 'Audio',
        '2' => 'Video',
        '3' => 'Applications',
        '4' => 'Games',
        '5' => 'Porn',
        '6' => 'Other',
        '101' => 'Music',
        '102' => 'Audio Books',
        '103' => 'Sound clips',
        '104' => 'FLAC',
        '199' => 'Other',
        '201' => 'Movies',
        '202' => 'Movies DVDR',
        '203' => 'Music videos',
        '204' => 'Movie Clips',
        '205' => 'TV-Shows',
        '206' => 'Handheld',
        '207' => 'HD Movies',
        '208' => 'HD TV-Shows',
        '209' => '3D',
        '299' => 'Other',
        '301' => 'Windows',
        '302' => 'Mac/Apple',
        '303' => 'UNIX',
        '304' => 'Handheld',
        '305' => 'IOS(iPad/iPhone)',
        '306' => 'Android',
        '399' => 'Other OS',
        '401' => 'PC',
        '402' => 'Mac/Apple',
        '403' => 'PSx',
        '404' => 'XBOX360',
        '405' => 'Wii',
        '406' => 'Handheld',
        '407' => 'IOS(iPad/iPhone)',
        '408' => 'Android',
        '499' => 'Other OS',
        '501' => 'Movies',
        '502' => 'Movies DVDR',
        '503' => 'Pictures',
        '504' => 'Games',
        '505' => 'HD-Movies',
        '506' => 'Movie Clips',
        '599' => 'Other',
        '601' => 'E-books',
        '602' => 'Comics',
        '603' => 'Pictures',
        '604' => 'Covers',
        '605' => 'Physibles',
        '699' => 'Other',
    ];

    public function collectData()
    {
        $keywords = explode(';', $this->getInput('q'));

        foreach ($keywords as $keyword) {
            $this->processKeyword($keyword);
        }
    }

    private function processKeyword($keyword)
    {
        $keyword = trim($keyword);
        switch ($this->getInput('crit')) {
            case 'search':
                $catCheck = $this->getInput('catCheck');
                if ($catCheck) {
                    $categories = $this->getInput('cat');
                    $query = sprintf(
                        '/q.php?q=%s&cat=%s',
                        rawurlencode($keyword),
                        rawurlencode($categories)
                    );
                } else {
                    $query = sprintf('/q.php?q=%s', rawurlencode($keyword));
                }
                break;
            case 'cat':
                $query = sprintf('/q.php?q=category:%s', rawurlencode($keyword));
                break;
            case 'usr':
                $query = sprintf('/q.php?q=user:%s', rawurlencode($keyword));
                break;
            default:
                returnClientError('Impossible');
        }
        $api = 'https://apibay.org';
        $json = getContents($api . $query);
        $result = json_decode($json);

        if ($result[0]->name === 'No results returned') {
            return;
        }
        foreach ($result as $torrent) {
            // This is the check for whether to include results from Trusted or VIP users
            if (
                $this->getInput('trusted')
                && !in_array($torrent->status, ['vip', 'trusted'])
            ) {
                continue;
            }
            $this->processTorrent($torrent);
        }
    }

    private function processTorrent($torrent)
    {
        // Extracted these trackers from the magnet links on thepiratebay.org
        $trackers = [
            'udp://tracker.coppersurfer.tk:6969/announce',
            'udp://tracker.openbittorrent.com:6969/announce',
            'udp://9.rarbg.to:2710/announce',
            'udp://9.rarbg.me:2780/announce',
            'udp://9.rarbg.to:2730/announce',
            'udp://tracker.opentrackr.org:1337',
            'http://p4p.arenabg.com:1337/announce',
            'udp://tracker.torrent.eu.org:451/announce',
            'udp://tracker.tiny-vps.com:6969/announce',
            'udp://open.stealth.si:80/announce',
        ];

        $magnetLink = sprintf(
            'magnet:?xt=urn:btih:%s&dn=%s',
            $torrent->info_hash,
            rawurlencode($torrent->name)
        );
        foreach ($trackers as $tracker) {
            // Build magnet link manually instead of using http_build_query because it
            // creates undesirable query such as ?tr[0]=foo&tr[1]=bar&tr[2]=baz
            $magnetLink .= '&tr=' . rawurlencode($tracker);
        }

        $item = [];

        $item['title'] = $torrent->name;
        // This uri should be a magnet link so that feed readers can easily pick it up.
        // However, rss-bridge only allows http or https schemes
        $item['uri'] = sprintf('%s/description.php?id=%s', self::URI, $torrent->id);
        $item['timestamp'] = $torrent->added;
        $item['author'] = $torrent->username;

        $content  = '<b>Type:</b> '
            . $this->renderCategory($torrent->category) . '<br>';
        $content .= "<b>Files:</b> $torrent->num_files<br>";
        $content .= '<b>Size:</b> ' . $this->renderSize($torrent->size) . '<br><br>';

        $content .= '<b>Uploaded:</b> '
            . $this->renderUploadDate($torrent->added) . '<br>';
        $content .= '<b>By:</b> ' . $this->renderUser($torrent) . '<br>';

        $content .= "<b>Seeders:</b> {$torrent->seeders}<br>";
        $content .= "<b>Leechers:</b> {$torrent->leechers}<br>";
        $content .= "<b>Info hash:</b> {$torrent->info_hash}<br><br>";

        if ($torrent->imdb) {
            $content .= '<b>Imdb:</b> '
                . $this->renderImdbLink($torrent->imdb) . '<br><br>';
        }

        $html = <<<HTML
<a href="%s">
	<img src="%s/images/icon-magnet.gif"> GET THIS TORRENT
</a>
<br>
HTML;
        $content .= sprintf($html, $magnetLink, self::STATIC_SERVER);

        $item['content'] = $content;

        $this->items[] = $item;
    }

    private function renderSize($size)
    {
        if ($size < 1024) {
            return $size . ' B';
        }
        if ($size < pow(1024, 2)) {
            return round($size / 1024, 2) . ' KB';
        }
        if ($size < pow(1024, 3)) {
            return round($size / pow(1024, 2), 2) . ' MB';
        }
        if ($size < pow(1024, 4)) {
            return round($size / pow(1024, 3), 2) . ' GB';
        }

        return round($size / pow(1024, 4), 2) . ' TB';
    }

    private function renderUploadDate($added)
    {
        return date('Y-m-d', $added ?: time());
    }

    private function renderCategory($category)
    {
        $mainCategory = sprintf(
            '<a href="%s/search.php?q=category:%s">%s</a>',
            self::URI,
            $category[0] . '00',
            self::CATEGORIES[$category[0]]
        );

        $subCategory = sprintf(
            '<a href="%s/search.php?q=category:%s">%s</a>',
            self::URI,
            $category,
            self::CATEGORIES[$category]
        );

        return sprintf('%s > %s', $mainCategory, $subCategory);
    }

    private function renderUser($torrent)
    {
        if ($torrent->username === 'Anonymous') {
            return $torrent->username . ' ' . $this->renderStatusImage($torrent->status);
        }
        return sprintf(
            '<a href="%s/search.php?q=user:%s">%s %s</a>',
            self::URI,
            $torrent->username,
            $torrent->username,
            $this->renderStatusImage($torrent->status)
        );
    }

    private function renderStatusImage($status)
    {
        if ($status == 'trusted') {
            return sprintf(
                '<img src="%s/images/trusted.png" title="Trusted"/>',
                self::STATIC_SERVER
            );
        }
        if ($status == 'vip') {
            return sprintf(
                '<img src="%s/images/vip.gif" title="VIP"/>',
                self::STATIC_SERVER
            );
        }
        if ($status == 'helper') {
            return sprintf(
                '<img src="%s/images/helper.png" title="Helper"/>',
                self::STATIC_SERVER
            );
        }
        if ($status == 'moderator') {
            return sprintf(
                '<img src="%s/images/moderator.gif" title="Moderator"/>',
                self::STATIC_SERVER
            );
        }
        if ($status == 'supermod') {
            return sprintf(
                '<img src="%s/images/supermod.png" title="Super Mod"/>',
                self::STATIC_SERVER
            );
        }
        if ($status == 'admin') {
            return sprintf(
                '<img src="%s/images/admin.gif" title="Admin"/>',
                self::STATIC_SERVER
            );
        }

        return '';
    }

    private function renderImdbLink($imdb)
    {
        return sprintf(
            '<a href="%s">%s</a>',
            "https://www.imdb.com/title/$imdb",
            "https://www.imdb.com/title/$imdb"
        );
    }
}
