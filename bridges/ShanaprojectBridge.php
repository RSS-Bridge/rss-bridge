<?php
class ShanaprojectBridge extends BridgeAbstract {
    const MAINTAINER = 'logmanoriginal';
    const NAME = 'Shanaproject Bridge';
    const URI = 'http://www.shanaproject.com';
    const DESCRIPTION = 'Returns a list of anime from the current Season Anime List';

    // Returns an html object for the Season Anime List (latest season)
    private function LoadSeasonAnimeList(){
        // First we need to find the URI to the latest season from the 'seasons' page searching for 'Season Anime List'
        $html = getSimpleHTMLDOM($this->getURI() . '/seasons');
        if(!$html)
            returnServerError('Could not load \'seasons\' page!');

        $season = $html->find('div.follows_menu/a', 1);
        if(!$season)
            returnServerError('Could not find \'Season Anime List\'!');

        $html = getSimpleHTMLDOM($this->getURI() . $season->href);
        if(!$html)
            returnServerError('Could not load \'Season Anime List\' from \'' . $season->innertext . '\'!');

        return $html;
    }

    // Extracts the anime title
    private function ExtractAnimeTitle($anime){
        $title = $anime->find('a', 0);
        if(!$title)
            returnServerError('Could not find anime title!');
        return trim($title->innertext);
    }

    // Extracts the anime URI
    private function ExtractAnimeURI($anime){
        $uri = $anime->find('a', 0);
        if(!$uri)
            returnServerError('Could not find anime URI!');
        return $this->getURI() . $uri->href;
    }

    // Extracts the anime release date (timestamp)
    private function ExtractAnimeTimestamp($anime){
        $timestamp = $anime->find('span.header_info_block', 1);
        if(!$timestamp)
            returnServerError('Could not find anime timestamp!');
        return strtotime($timestamp->innertext);
    }

    // Extracts the anime studio name (author)
    private function ExtractAnimeAuthor($anime){
        $author = $anime->find('span.header_info_block', 2);
        if(!$author)
            return; // Sometimes the studio is unknown, so leave empty
        return trim($author->innertext);
    }

    // Extracts the episode information (x of y released)
    private function ExtractAnimeEpisodeInformation($anime){
        $episode = $anime->find('div.header_info_episode', 0);
        if(!$episode)
            returnServerError('Could not find anime episode information!');
        return preg_replace('/\r|\n/', ' ', $episode->plaintext);
    }

    // Extracts the background image
    private function ExtractAnimeBackgroundImage($anime){
        // Getting the picture is a little bit tricky as it is part of the style.
        // Luckily the style is part of the parent div :)

        if(preg_match("/url\(\/\/([^\)]+)\)/i", $anime->parent->style, $matches))
            return $matches[1];

        returnServerError('Could not extract background image!');
    }

    // Builds an URI to search for a specific anime (subber is left empty)
    private function BuildAnimeSearchURI($anime){
        return $this->getURI() . '/search/?title=' . urlencode($this->ExtractAnimeTitle($anime)) . '&subber=';
    }

    // Builds the content string for a given anime
    private function BuildAnimeContent($anime){
        // We'll use a template string to place our contents
        return '<a href="' . $this->ExtractAnimeURI($anime) . '">
                    <img src="http://' . $this->ExtractAnimeBackgroundImage($anime) . '" alt="' . htmlspecialchars($this->ExtractAnimeTitle($anime)) . '" style="border: 1px solid black">
                </a><br>
                <p>' . $this->ExtractAnimeEpisodeInformation($anime) . '</p><br>
                <p><a href="' . $this->BuildAnimeSearchURI($anime) . '">Search episodes</a></p>';
    }

    public function collectData(){
        $html = $this->LoadSeasonAnimeList();

        $animes = $html->find('div.header_display_box_info');
        if(!$animes)
            returnServerError('Could not find anime headers!');

        foreach($animes as $anime){
            $item = array();
            $item['title'] = $this->ExtractAnimeTitle($anime);
            $item['author'] = $this->ExtractAnimeAuthor($anime);
            $item['uri'] = $this->ExtractAnimeURI($anime);
            $item['timestamp'] = $this->ExtractAnimeTimestamp($anime);
            $item['content'] = $this->BuildAnimeContent($anime);
            $this->items[] = $item;
        }
    }
}
