<?php

/**
 * IvooxRssBridge
 * Returns the latest search result
 * TODO: support podcast episodes list
 */
class IvooxBridge extends BridgeAbstract
{
    const NAME = 'Ivoox Bridge';
    const URI = 'https://www.ivoox.com/';
    const CACHE_TIMEOUT = 10800; // 3h
    const DESCRIPTION = 'Returns the 10 newest episodes by keyword search';
    const MAINTAINER = 'xurxof'; // based on YoutubeBridge by mitsukarenai
    const PARAMETERS = [
        'Search result' => [
            's' => [
                'name' => 'keyword',
                'required' => true,
                'exampleValue' => 'car'
            ]
        ]
    ];

    private function ivBridgeAddItem(
        $episode_link,
        $podcast_name,
        $episode_title,
        $author_name,
        $episode_description,
        $publication_date,
        $episode_duration
    ) {
        $item = [];
        $item['title'] = htmlspecialchars_decode($podcast_name . ': ' . $episode_title);
        $item['author'] = $author_name;
        $item['timestamp'] = $publication_date;
        $item['uri'] = $episode_link;
        $item['content'] = '<a href="' . $episode_link . '">' . $podcast_name . ': ' . $episode_title
            . '</a><br />Duration: ' . $episode_duration
            . '<br />Description:<br />' . $episode_description;
        $this->items[] = $item;
    }

    private function ivBridgeParseHtmlListing($html)
    {
        $limit = 4;
        $count = 0;

        foreach ($html->find('div.flip-container') as $flipper) {
            $linkcount = 0;
            if (!empty($flipper->find('div.modulo-type-banner'))) {
                // ad
                continue;
            }

            if ($count < $limit) {
                foreach ($flipper->find('div.header-modulo') as $element) {
                    foreach ($element->find('a') as $link) {
                        if ($linkcount == 0) {
                            $episode_link = $link->href;
                            $episode_title = $link->title;
                        } elseif ($linkcount == 1) {
                            $author_link = $link->href;
                            $author_name = $link->title;
                        } elseif ($linkcount == 2) {
                            $podcast_link = $link->href;
                            $podcast_name = $link->title;
                        }

                        $linkcount++;
                    }
                }

                $episode_description = $flipper->find('button.btn-link', 0)->getAttribute('data-content');
                $episode_duration = $flipper->find('p.time', 0)->innertext;
                $publication_date = $flipper->find('li.date', 0)->getAttribute('title');

                // alternative date_parse_from_format
                // or DateTime::createFromFormat('G:i - d \d\e M \d\e Y', $publication);
                // TODO: month name translations, due function doesn't support locale

                $a = strptime($publication_date, '%H:%M - %d de %b. de %Y'); // obsolete function, uses c libraries
                $publication_date = mktime(0, 0, 0, $a['tm_mon'] + 1, $a['tm_mday'], $a['tm_year'] + 1900);

                $this->ivBridgeAddItem(
                    $episode_link,
                    $podcast_name,
                    $episode_title,
                    $author_name,
                    $episode_description,
                    $publication_date,
                    $episode_duration
                );
                $count++;
            }
        }
    }

    public function collectData()
    {
        // store locale, change to spanish
        $originalLocales = explode(';', setlocale(LC_ALL, 0));
        setlocale(LC_ALL, 'es_ES.utf8');

        $xml = '';
        $html = '';
        $url_feed = '';
        if ($this->getInput('s')) { /* Search  modes */
            $this->request = str_replace(' ', '-', $this->getInput('s'));
            $url_feed = self::URI . urlencode($this->request) . '_sb_f_1.html?o=uploaddate';
        } else {
            returnClientError('Not valid mode at IvooxBridge');
        }

        $dom = getSimpleHTMLDOM($url_feed);
        $this->ivBridgeParseHtmlListing($dom);

        // restore locale

        foreach ($originalLocales as $localeSetting) {
            if (strpos($localeSetting, '=') !== false) {
                [$category, $locale] = explode('=', $localeSetting);
                if (! defined($category)) {
                    continue;
                }
                $category = constant($category);
            } else {
                $category = LC_ALL;
                $locale = $localeSetting;
            }

            setlocale($category, $locale);
        }
    }
}
