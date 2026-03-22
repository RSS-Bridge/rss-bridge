<?php

declare(strict_types=1);

class MinecraftBridge extends BridgeAbstract
{
    const NAME = 'Minecraft';
    const URI = 'https://www.minecraft.net';
    const DESCRIPTION = 'Catch up on the latest Minecraft articles';
    const MAINTAINER = 'tillcash';
    const PARAMETERS = [
        [
            'category' => [
                'type' => 'list',
                'name' => 'Category',
                'values' => [
                    'All' => 'all',
                    'Deep Dives' => 'minecraft:stockholm/deep-dives',
                    'News' => 'minecraft:stockholm/news',
                    'Marketplace' => 'minecraft:stockholm/marketplace',
                ],
                'title' => 'Choose article category',
                'defaultValue' => 'all',
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://www.minecraft.net/etc.clientlibs/minecraftnet/clientlibs/clientlib-site/resources/favicon.ico';
    }

    public function collectData()
    {
        /* Removing either "category=News" or "newsOnly=false" causes many articles to not be visible */
        $json = getContents('https://net-secondary.web.minecraft-services.net/api/v1.0/en-us/search?sortType=Recent&category=News&newsOnly=false');

        $data = json_decode($json);
        if ($data === null || empty($data->result->results)) {
            throwServerException('Invalid or empty content');
        }

        $category = $this->getInput('category');

        foreach ($data->result->results as $article) {
            if ($category !== 'all' && in_array($category, $article->tags)) {
                continue;
            }

            $imageUrl = $article->image;

            /* All posts have this article-page tag. Removing it. */
            $tags = array_filter($article->tags, function ($value) {
                return $value !== 'article-page';
            });
            $tags = array_map([$this, 'normalizeTags'], $tags);

            $this->items[] = [
                'title' => trim($article->title),
                'uid' => parse_url($article->url, PHP_URL_PATH),
                'uri' => $article->url,
                'timestamp' => $article->time,
                'author' => $article->author,
                'content' => $article->description,
                'categories' => $tags,
                'enclosures' => $imageUrl ? [$imageUrl] : [],
            ];
        }
    }
    /**
     * For compatibility for tags from before 2026-02-12
     */
    private function normalizeTags($tag)
    {
        $index = strpos($tag, '/');
        if ($index !== false) {
            $tag = substr($tag, $index + 1);
        }
        $tag = str_replace('-', ' ', $tag);
        # Backwards compatibility with old tags
        return ucwords($tag);
    }
}
