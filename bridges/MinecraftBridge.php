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
                    'Deep Dives' => 'Deep Dives',
                    'News' => 'News',
                    'Marketplace' => 'Marketplace',
                    'Merch' => 'Merch',
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
        $json = getContents('https://www.minecraft.net/content/minecraftnet/language-masters/en-us/_jcr_content.articles.page-1.json');

        $data = json_decode($json);
        if ($data === null || empty($data->article_grid)) {
            throwServerException('Invalid or empty content');
        }

        $category = $this->getInput('category');

        foreach ($data->article_grid as $article) {
            if ($category !== 'all' && $category !== $article->primary_category) {
                continue;
            }

            $imageUrl = $this->getEncodedImageUrl($article->default_tile->image->imageURL);

            $this->items[] = [
                'title' => trim($article->default_tile->title),
                'uid' => $article->article_url,
                'uri' => urljoin(self::URI, $article->article_url),
                'content' => $article->default_tile->sub_header,
                'categories' => [$article->primary_category],
                'enclosures' => $imageUrl ? [$imageUrl] : [],
            ];
        }
    }

    private function getEncodedImageUrl(string $path): ?string
    {
        $path = explode('/', ltrim($path, '/'));
        $path = array_map('rawurlencode', $path);
        $path = implode('/', $path);

        $url = urljoin(self::URI, $path);

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
