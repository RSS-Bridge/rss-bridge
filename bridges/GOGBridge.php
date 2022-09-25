<?php

class GOGBridge extends BridgeAbstract
{
    const NAME = 'GOGBridge';
    const MAINTAINER = 'teromene';
    const URI = 'https://gog.com';
    const DESCRIPTION = 'Returns the latest releases from GOG.com';

    public function collectData()
    {
        $values = getContents('https://www.gog.com/games/ajax/filtered?limit=25&sort=new');
        $decodedValues = json_decode($values);

        $limit = 0;
        foreach ($decodedValues->products as $game) {
            $item = [];
            $item['author'] = $game->developer . ' / ' . $game->publisher;
            $item['title'] = $game->title;
            $item['id'] = $game->id;
            $item['uri'] = self::URI . $game->url;
            $item['content'] = $this->buildGameContentPage($game);
            $item['timestamp'] = $game->globalReleaseDate;

            foreach ($game->gallery as $image) {
                $item['enclosures'][] = $image . '.jpg';
            }

            $this->items[] = $item;
            $limit += 1;

            if ($limit == 10) {
                break;
            }
        }
    }

    private function buildGameContentPage($game)
    {
        $gameDescriptionText = getContents('https://api.gog.com/products/' . $game->id . '?expand=description');

        $gameDescriptionValue = json_decode($gameDescriptionText);

        $content = 'Genres: ';
        $content .= implode(', ', $game->genres);

        $content .= '<br />Supported Platforms: ';
        if ($game->worksOn->Windows) {
            $content .= 'Windows ';
        }
        if ($game->worksOn->Mac) {
            $content .= 'Mac ';
        }
        if ($game->worksOn->Linux) {
            $content .= 'Linux ';
        }

        $content .= '<br />' . $gameDescriptionValue->description->full;

        return $content;
    }
}
