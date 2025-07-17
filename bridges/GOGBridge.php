<?php

class GOGBridge extends BridgeAbstract
{
    const NAME = 'GOGBridge';
    const MAINTAINER = 'teromene';
    const URI = 'https://gog.com';
    const DESCRIPTION = 'Returns the latest releases from GOG.com';

    public function collectData()
    {
        $values = getContents('https://catalog.gog.com/v1/catalog?limit=48&order=desc%3AstoreReleaseDate');
        $decodedValues = json_decode($values);

        $limit = 0;
        foreach ($decodedValues->products as $game) {
            $item = [];
            $item['author'] = implode(', ', $game->developers) . ' / ' . implode(', ', $game->publishers);
            $item['title'] = $game->title;
            $item['id'] = $game->id;
            $item['uri'] = $game->storeLink;
            $item['content'] = $this->buildGameContentPage($game);

            foreach ($game->screenshots as $image) {
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
        $content .= implode(', ', array_column($game->genres, 'name'));

        $content .= '<br />Supported Platforms: ';
        $content .= implode(', ', $game->operatingSystems);

        $content .= '<br />' . $gameDescriptionValue->description->full;

        return $content;
    }
}
