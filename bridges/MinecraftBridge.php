<?php

class MinecraftBridge extends BridgeAbstract
{
    const NAME = 'Minecraft';
    const URI = 'https://www.minecraft.net';
    const DESCRIPTION = 'Catch up on the latest Minecraft articles';
    const MAINTAINER = 'tillcash';

    public function getIcon()
    {
        return 'https://www.minecraft.net/etc.clientlibs/minecraftnet/clientlibs/clientlib-site/resources/favicon.ico';
    }

    public function collectData()
    {
        $json = getContents(
            'https://www.minecraft.net/content/minecraftnet/language-masters/en-us/_jcr_content.articles.page-1.json'
        );

        $articles = json_decode($json);

        if ($articles === null) {
            returnServerError('Failed to decode JSON content.');
        }

        foreach ($articles->article_grid as $article) {
            $this->items[] = [
                'title' => $article->default_tile->title,
                'uid' => $article->article_url,
                'uri' => self::URI . $article->article_url,
                'content' => $article->default_tile->sub_header,
            ];
        }
    }
}
