<?php

class AnimeUltimeBridge extends BridgeAbstract
{
    const MAINTAINER = 'ORelio';
    const NAME = 'Anime-Ultime';
    const URI = 'http://www.anime-ultime.net/';
    const CACHE_TIMEOUT = 10800; // 3h
    const DESCRIPTION = 'Returns the newest releases posted on Anime-Ultime.';
    const PARAMETERS = [ [
        'type' => [
        'name' => 'Type',
        'type' => 'list',
        'values' => [
            'Everything' => '',
            'Anime' => 'A',
            'Drama' => 'D',
            'Tokusatsu' => 'T'
            ]
        ]
    ]];

    private $filter = 'Releases';

    public function collectData()
    {
        //Add type filter if provided
        $typeFilter = array_search(
            $this->getInput('type'),
            self::PARAMETERS[$this->queriedContext]['type']['values']
        );

        //Build date and filters for making requests
        $thismonth = date('mY') . $typeFilter;
        $lastmonth = date('mY', mktime(0, 0, 0, date('n') - 1, 1, date('Y'))) . $typeFilter;

        //Process each HTML page until having 10 releases
        $processedOK = 0;
        foreach ([$thismonth, $lastmonth] as $requestFilter) {
            $url = self::URI . 'history-0-1/' . $requestFilter;
            $html = getContents($url);
            // Convert html from iso-8859-1 => utf8
            $html = utf8_encode($html);
            $html = str_get_html($html);

            //Relases are sorted by day : process each day individually
            foreach ($html->find('div.history', 0)->find('h3') as $daySection) {
                //Retrieve day and build date information
                $dateString = $daySection->plaintext;
                $day = intval(substr($dateString, strpos($dateString, ' ') + 1, 2));
                $item_date = strtotime(str_pad($day, 2, '0', STR_PAD_LEFT)
                . '-'
                . substr($requestFilter, 0, 2)
                . '-'
                . substr($requestFilter, 2, 4));

                //<h3>day</h3><br /><table><tr> <-- useful data in table rows
                $release = $daySection->next_sibling()->next_sibling()->first_child();

                //Process each release of that day, ignoring first table row: contains table headers
                while (!is_null($release = $release->next_sibling())) {
                    if (count($release->find('td')) > 0) {
                        //Retrieve metadata from table columns
                        $item_link_element = $release->find('td', 0)->find('a', 0);
                        $item_uri = self::URI . $item_link_element->href;
                        $item_name = html_entity_decode($item_link_element->plaintext);

                        $item_image = self::URI . substr(
                            $item_link_element->onmouseover,
                            37,
                            strpos($item_link_element->onmouseover, ' ', 37) - 37
                        );

                        $item_episode = html_entity_decode(
                            str_pad(
                                $release->find('td', 1)->plaintext,
                                2,
                                '0',
                                STR_PAD_LEFT
                            )
                        );

                        $item_fansub = $release->find('td', 2)->plaintext;
                        $item_type = $release->find('td', 4)->plaintext;

                        if (!empty($item_uri)) {
                            // Retrieve description from description page
                            $html_item = getContents($item_uri);
                            // Convert html from iso-8859-1 => utf8
                            $html_item = utf8_encode($html_item);
                            $item_description = substr(
                                $html_item,
                                strpos($html_item, 'class="principal_contain" align="center">') + 41
                            );
                            $item_description = substr(
                                $item_description,
                                0,
                                strpos($item_description, '<div id="table">')
                            );

                            // Convert relative image src into absolute image src, remove line breaks
                            $item_description = defaultLinkTo($item_description, self::URI);
                            $item_description = str_replace("\r", '', $item_description);
                            $item_description = str_replace("\n", '', $item_description);

                            //Build and add final item
                            $item = [];
                            $item['uri'] = $item_uri;
                            $item['title'] = $item_name . ' ' . $item_type . ' ' . $item_episode;
                            $item['author'] = $item_fansub;
                            $item['timestamp'] = $item_date;
                            $item['enclosures'] = [$item_image];
                            $item['content'] = $item_description;
                            $this->items[] = $item;
                            $processedOK++;

                            //Stop processing once limit is reached
                            if ($processedOK >= 10) {
                                return;
                            }
                        }
                    }
                }
            }
        }
    }

    public function getName()
    {
        if (!is_null($this->getInput('type'))) {
            $typeFilter = array_search(
                $this->getInput('type'),
                self::PARAMETERS[$this->queriedContext]['type']['values']
            );

            return 'Latest ' . $typeFilter . ' - Anime-Ultime Bridge';
        }

        return parent::getName();
    }
}
