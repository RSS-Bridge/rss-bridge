<?php

class ShanaprojectBridge extends BridgeAbstract
{
    const MAINTAINER = 'logmanoriginal';
    const NAME = 'Shanaproject Bridge';
    const URI = 'https://www.shanaproject.com';
    const DESCRIPTION = 'Returns a list of anime from the current Season Anime List';
    const PARAMETERS = [
        [
            'min_episodes' => [
                'name' => 'Minimum Episodes',
                'type' => 'number',
                'title' => 'Minimum number of episodes before including in feed',
                'defaultValue' => 0,
            ],
            'min_total_episodes' => [
                'name' => 'Minimum Total Episodes',
                'type' => 'number',
                'title' => 'Minimum total number of episodes before including in feed',
                'defaultValue' => 0,
            ],
            'require_banner' => [
                'name' => 'Require Banner',
                'type' => 'checkbox',
                'title' => 'Only include anime with custom banner image',
                'defaultValue' => false,
            ],
        ],
    ];

    private $uri;

    public function getURI()
    {
        return $this->uri ?? parent::getURI();
    }

    public function collectData()
    {
        $html = $this->loadSeasonAnimeList();

        $animes = $html->find('div.header_display_box_info')
            or throwServerException('Could not find anime headers!');

        $min_episodes = $this->getInput('min_episodes') ?: 0;
        $min_total_episodes = $this->getInput('min_total_episodes') ?: 0;

        foreach ($animes as $anime) {
            [
                $episodes_released,
                /* of */,
                $episodes_total
            ] = explode(' ', $this->extractAnimeEpisodeInformation($anime));

            // Skip if not enough episodes yet
            if ($episodes_released < $min_episodes) {
                continue;
            }

            // Skip if too many episodes in total
            if ($episodes_total !== '?' && $episodes_total < $min_total_episodes) {
                continue;
            }

            // Skip if https://static.shanaproject.com/no-art.jpg
            if (
                $this->getInput('require_banner')
                && strpos($this->extractAnimeBackgroundImage($anime), 'no-art') !== false
            ) {
                continue;
            }

            $this->items[] = [
                'title' => $this->extractAnimeTitle($anime),
                'author' => $this->extractAnimeAuthor($anime),
                'uri' => $this->extractAnimeUri($anime),
                'timestamp' => $this->extractAnimeTimestamp($anime),
                'content' => $this->buildAnimeContent($anime),
            ];
        }
    }

    // Returns an html object for the Season Anime List (latest season)
    private function loadSeasonAnimeList()
    {
        $html = getSimpleHTMLDOM(self::URI . '/seasons');

        $html = defaultLinkTo($html, self::URI . '/seasons');

        $season = $html->find('div.follows_menu > a', 1)
            or throwServerException('Could not find \'Season Anime List\'!');

        $html = getSimpleHTMLDOM($season->href);

        $this->uri = $season->href;

        $html = defaultLinkTo($html, $season->href);

        return $html;
    }

    // Extracts the anime title
    private function extractAnimeTitle($anime)
    {
        $title = $anime->find('a', 0)
            or throwServerException('Could not find anime title!');
        return trim($title->innertext);
    }

    // Extracts the anime URI
    private function extractAnimeUri($anime)
    {
        $uri = $anime->find('a', 0)
            or throwServerException('Could not find anime URI!');
        return $uri->href;
    }

    // Extracts the anime release date (timestamp)
    private function extractAnimeTimestamp($anime)
    {
        $timestamp = $anime->find('span.header_info_block', 1);

        if (!$timestamp) {
            return null;
        }

        return strtotime($timestamp->innertext);
    }

    // Extracts the anime studio name (author)
    private function extractAnimeAuthor($anime)
    {
        $author = $anime->find('span.header_info_block', 2);

        if (!$author) {
            return null; // Sometimes the studio is unknown, so leave empty
        }

        return trim($author->innertext);
    }

    // Extracts the episode information (x of y released)
    private function extractAnimeEpisodeInformation($anime)
    {
        $episode = $anime->find('div.header_info_episode', 0)
            or throwServerException('Could not find anime episode information!');

        $retVal = preg_replace('/\r|\n/', ' ', $episode->plaintext);
        $retVal = preg_replace('/\s+/', ' ', $retVal);

        return $retVal;
    }

    // Extracts the background image
    private function extractAnimeBackgroundImage($anime)
    {
        // Getting the picture is a little bit tricky as it is part of the style.
        // Luckily the style is part of the parent div :)

        if (preg_match('/url\(\/\/([^\)]+)\)/i', $anime->parent->style, $matches)) {
            return $matches[1];
        }

        throwServerException('Could not extract background image!');
    }

    // Builds an URI to search for a specific anime (subber is left empty)
    private function buildAnimeSearchUri($anime)
    {
        return self::URI
        . '/search/?title='
        . urlencode($this->extractAnimeTitle($anime))
        . '&subber=';
    }

    // Builds the content string for a given anime
    private function buildAnimeContent($anime)
    {
        // We'll use a template string to place our contents
        return '<a href="'
        . $this->extractAnimeUri($anime)
        . '"><img src="http://'
        . $this->extractAnimeBackgroundImage($anime)
        . '" alt="'
        . htmlspecialchars($this->extractAnimeTitle($anime))
        . '" style="border: 1px solid black"></a><br><p>'
        . $this->extractAnimeEpisodeInformation($anime)
        . '</p><br><p><a href="'
        . $this->buildAnimeSearchUri($anime)
        . '">Search episodes</a></p>';
    }
}
