<?php

declare(strict_types=1);

final class PokemonNewsBridge extends BridgeAbstract
{
    const NAME = 'Pokemon.com news';
    const URI = 'https://www.pokemon.com/us/pokemon-news';
    const DESCRIPTION = 'Fetches the latest news from pokemon.com';
    const MAINTAINER = 'dvikan';

    public function collectData()
    {
        // todo: parse json api instead: https://www.pokemon.com/api/1/us/news/get-news.json
        $url = 'https://www.pokemon.com/us/pokemon-news';
        $dom = getSimpleHTMLDOM($url);
        $haystack = (string)$dom;
        if (str_contains($haystack, 'Request unsuccessful. Incapsula incident')) {
            throw new \Exception('Blocked by anti-bot');
        }
        foreach ($dom->find('.news-list ul li') as $item) {
            $title = $item->find('h3', 0)->plaintext;
            $description = $item->find('p.hidden-mobile', 0);
            $dateString = $item->find('p.date', 0)->plaintext;
            // e.g. September 15, 2022
            $createdAt = date_create_from_format('F d, Y', $dateString);
            // todo:
            $tagsString = $item->find('p.tags', 0)->plaintext;
            $path = $item->find('a', 0)->href;
            $imagePath = $item->find('img', 0)->src;
            $tags = explode('&', $tagsString);
            $tags = array_map('trim', $tags);

            $this->items[] = [
                'title' => $title,
                'uri' => sprintf('https://www.pokemon.com%s', $path),
                'timestamp' => $createdAt ? $createdAt->getTimestamp() : time(),
                'categories' => $tags,
                'content' => sprintf(
                    '<img src="https://pokemon.com%s"><br><br>%s',
                    $imagePath,
                    $description ? $description->plaintext : ''
                ),
            ];
        }
    }
}
