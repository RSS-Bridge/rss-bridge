<?php

class AnidexBridge extends BridgeAbstract
{
    const MAINTAINER = 'ORelio';
    const NAME = 'Anidex';
    const URI = 'http://anidex.info/'; // anidex.info has ddos-guard so we need to use anidex.moe
    const ALTERNATE_URI = 'https://anidex.moe/'; // anidex.moe returns 301 unless Host is set to anidex.info
    const ALTERNATE_HOST = 'anidex.info'; // Correct host for requesting anidex.moe without 301 redirect
    const DESCRIPTION = 'Returns the newest torrents, with optional search criteria.';
    const PARAMETERS = [
        [
            'id' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'All categories' => '0',
                    'Anime' => '1,2,3',
                    'Anime - Sub' => '1',
                    'Anime - Raw' => '2',
                    'Anime - Dub' => '3',
                    'Live Action' => '4,5',
                    'Live Action - Sub' => '4',
                    'Live Action - Raw' => '5',
                    'Light Novel' => '6',
                    'Manga' => '7,8',
                    'Manga - Translated' => '7',
                    'Manga - Raw' => '8',
                    'Music' => '9,10,11',
                    'Music - Lossy' => '9',
                    'Music - Lossless' => '10',
                    'Music - Video' => '11',
                    'Games' => '12',
                    'Applications' => '13',
                    'Pictures' => '14',
                    'Adult Video' => '15',
                    'Other' => '16'
                ]
            ],
            'lang_id' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'All languages' => '0',
                    'English' => '1',
                    'Japanese' => '2',
                    'Polish' => '3',
                    'Serbo-Croatian' => '4',
                    'Dutch' => '5',
                    'Italian' => '6',
                    'Russian' => '7',
                    'German' => '8',
                    'Hungarian' => '9',
                    'French' => '10',
                    'Finnish' => '11',
                    'Vietnamese' => '12',
                    'Greek' => '13',
                    'Bulgarian' => '14',
                    'Spanish (Spain)' => '15',
                    'Portuguese (Brazil)' => '16',
                    'Portuguese (Portugal)' => '17',
                    'Swedish' => '18',
                    'Arabic' => '19',
                    'Danish' => '20',
                    'Chinese (Simplified)' => '21',
                    'Bengali' => '22',
                    'Romanian' => '23',
                    'Czech' => '24',
                    'Mongolian' => '25',
                    'Turkish' => '26',
                    'Indonesian' => '27',
                    'Korean' => '28',
                    'Spanish (LATAM)' => '29',
                    'Persian' => '30',
                    'Malaysian' => '31'
                ]
            ],
            'group_id' => [
                'name' => 'Group ID',
                'type' => 'number'
            ],
            'r' => [
                'name' => 'Hide Remakes',
                'type' => 'checkbox'
            ],
            'b' => [
                'name' => 'Only Batches',
                'type' => 'checkbox'
            ],
            'a' => [
                'name' => 'Only Authorized',
                'type' => 'checkbox'
            ],
            'q' => [
                'name' => 'Keyword',
                'description' => 'Keyword(s)',
                'type' => 'text'
            ],
            'h' => [
                'name' => 'Adult content',
                'type' => 'list',
                'values' => [
                    'No filter' => '0',
                    'Hide +18' => '1',
                    'Only +18' => '2'
                ]
            ]
        ]
    ];

    public function collectData()
    {
        // Build Search URL from user-provided parameters
        $search_url = self::ALTERNATE_URI . '?s=upload_timestamp&o=desc';
        foreach (['id', 'lang_id', 'group_id'] as $param_name) {
            $param = $this->getInput($param_name);
            if (!empty($param) && intval($param) != 0 && ctype_digit(str_replace(',', '', $param))) {
                $search_url .= '&' . $param_name . '=' . $param;
            }
        }
        foreach (['r', 'b', 'a'] as $param_name) {
            $param = $this->getInput($param_name);
            if (!empty($param) && boolval($param)) {
                $search_url .= '&' . $param_name . '=1';
            }
        }
        $query = $this->getInput('q');
        if (!empty($query)) {
            $search_url .= '&q=' . urlencode($query);
        }
        $opt = [];
        $h = $this->getInput('h');
        if (!empty($h) && intval($h) != 0 && ctype_digit($h)) {
            $opt[CURLOPT_COOKIE] = 'anidex_h_toggle=' . $h;
        }

        // We need to use a different Host HTTP header to reach the correct page on ALTERNATE_URI
        $headers = ['Host: ' . self::ALTERNATE_HOST];

        // The HTTPS certificate presented by anidex.moe is for anidex.info. We need to ignore this.
        // As a consequence, the bridge is intentionally marked as insecure by setting self::URI to http://
        $opt[CURLOPT_SSL_VERIFYHOST] = 0;
        $opt[CURLOPT_SSL_VERIFYPEER] = 0;

        // Retrieve torrent listing from search results, which does not contain torrent description
        $html = getSimpleHTMLDOM($search_url, $headers, $opt);
        $links = $html->find('a');
        $results = [];
        foreach ($links as $link) {
            if (strpos($link->href, '/torrent/') === 0 && !in_array($link->href, $results)) {
                $results[] = $link->href;
            }
        }
        if (empty($results) && empty($this->getInput('q'))) {
            throwServerException('No results from Anidex: ' . $search_url);
        }

        //Process each item individually
        foreach ($results as $element) {
            //Limit total amount of requests
            if (count($this->items) >= 20) {
                break;
            }

            $torrent_id = str_replace('/torrent/', '', $element);

            //Ignore entries without valid torrent ID
            if ($torrent_id != 0 && ctype_digit($torrent_id)) {
                //Retrieve data for this torrent ID
                $item_browse_uri = self::URI . 'torrent/' . $torrent_id;
                $item_fetch_uri = self::ALTERNATE_URI . 'torrent/' . $torrent_id;

                //Retrieve full description from torrent page (cached for 24 hours: 86400 seconds)
                if ($item_html = getSimpleHTMLDOMCached($item_fetch_uri, 86400, $headers, $opt)) {
                    //Retrieve data from page contents
                    $item_title = str_replace(' (Torrent) - AniDex ', '', $item_html->find('title', 0)->plaintext);
                    $item_desc = $item_html->find('div.panel-body', 0);
                    $item_author = trim($item_html->find('span.fa-user', 0)->parent()->plaintext);
                    $item_date = strtotime(trim($item_html->find('span.fa-clock', 0)->parent()->plaintext));
                    $item_image = $this->getURI() . 'images/user_logos/default.png';

                    //Check for description-less torrent andn optionally extract image
                    $desc_title_found = false;
                    foreach ($item_html->find('h3.panel-title') as $h3) {
                        if (strpos($h3, 'Description') !== false) {
                            $desc_title_found = true;
                            break;
                        }
                    }
                    if ($desc_title_found) {
                        //Retrieve image for thumbnail or generic logo fallback
                        foreach ($item_desc->find('img') as $img) {
                            if (strpos($img->src, 'prez') === false) {
                                $item_image = $img->src;
                                break;
                            }
                        }
                        $item_desc = trim($item_desc->innertext);
                    } else {
                        $item_desc = '<em>No description.</em>';
                    }

                    //Build and add final item
                    $item = [];
                    $item['uri'] = $item_browse_uri;
                    $item['title'] = $item_title;
                    $item['author'] = $item_author;
                    $item['timestamp'] = $item_date;
                    $item['enclosures'] = [$item_image];
                    $item['content'] = $item_desc;
                    $this->items[] = $item;
                }
            }
            $element = null;
        }
        $results = null;
    }
}
