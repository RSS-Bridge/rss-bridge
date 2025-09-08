<?php

declare(strict_types=1);

class FFXIVLodestoneNewsBridge extends BridgeAbstract
{
    const NAME = 'FFXIV Lodestone News';
    const URI = 'https://eu.finalfantasyxiv.com/lodestone/';
    const DESCRIPTION = 'Catch up on the latest FFXIV Lodestone articles';
    const MAINTAINER = 'nairol203';
    const PARAMETERS = [
        [
            'region' => [
                'type' => 'list',
                'name' => 'Region',
                'values' => [
                    'North America' => 'na',
                    'Europe' => 'eu',
                    'France' => 'fr',
                    'Germany' => 'de',
                    'Japan' => 'jp',
                ],
                'title' => 'Choose region',
                'defaultValue' => 'eu',
            ],
            'category' => [
                'type' => 'list',
                'name' => 'Category',
                'values' => [
                    'All' => 'feed',
                    'Topics' => 'topics',
                    'Notices' => 'notices',
                    'Maintenance' => 'maintenance',
                    'Updates' => 'updates',
                    'Status' => 'status',
                    'Developers\' Blog' => 'developers',
                ],
                'title' => 'Choose article category',
                'defaultValue' => 'all',
            ]
        ]
    ];

    public function getIcon()
    {
        return 'https://lds-img.finalfantasyxiv.com/pc/global/images/favicon.ico?1720069015';
    }

    public function collectData()
    {
        $json = getContents(
            "https://lodestonenews.com/news/{$this->getInput('category')}?locale={$this->getInput('region')}"
        );

        $articles = json_decode($json);

        if ($articles === null) {
            throwServerException('Failed to decode JSON content.');
        }

        foreach ($articles as $article) {
            $this->items[] = [
                'uri' => $article->url,
                'title' => $article->title,
                'timestamp' => $article->time,
                'content' => isset($article->description) ? $article->description : '',
                'enclosures' => isset($article->image) ? [$article->image] : [],
                'categories' => [ucfirst(isset($article->category) ? $article->category : $this->getInput('category'))],
                'uid' => $article->id,
            ];
        }
    }
}
