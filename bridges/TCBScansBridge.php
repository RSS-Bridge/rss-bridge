<?php

class TCBScansBridge extends BridgeAbstract
{
    const NAME = 'TCB Scans Bridge';
    const URI = 'https://tcbscans.me/';
    const DESCRIPTION = 'Returns the latest chapter from a TCB Scans project';
    const MAINTAINER = 'osvfj';
    const PARAMETERS = [[
        'manga' => [
            'name' => 'Manga',
            'title' => 'Select your prefered manga',
            'exampleValue' => 'One Piece',
            'type' => 'list',
            'values' => [
                'Ace Novel - Manga Adaptation' => 'mangas/1/ace-novel-manga-adaptation',
                'Attack on Titan' => 'mangas/8/attack-on-titan',
                'Black Clover' => 'mangas/3/black-clover',
                'Black Clover Gaiden: Quartet Knights' => 'mangas/24/black-clover-gaiden-quartet-knights',
                'Bleach' => 'mangas/2/bleach',
                'Build King' => 'mangas/9/build-king',
                'Chainsaw Man' => 'mangas/13/chainsaw-man',
                'Demon Slayer: Kimetsu no Yaiba ' => 'mangas/19/demon-slayer-kimetsu-no-yaiba',
                'Haikyuu!! (New Special!)' => 'mangas/11/haikyu-special',
                'Hunter X Hunter' => 'mangas/15/hunter-x-hunter',
                'Jujutsu Kaisen' => 'mangas/4/jujutsu-kaisen',
                'My Hero Academia' => 'mangas/6/my-hero-academia',
                "My Hero Academia One-Shot: You're Next!!" => 'mangas/25/my-hero-academia-one-shot-you-re-next',
                'One Piece ' => 'mangas/5/one-piece',
                'One Piece - Nami vs Kalifa by Boichi' => 'mangas/12/one-piece-nami-vs-kalifa-by-boichi',
                'One-Punch Man' => 'mangas/10/one-punch-man',
                'Spy X Family' => 'mangas/23/spy-x-family',
            ],
        ],
        'full_chapter' => [
            'name' => 'Load images in the item',
            'type' => 'checkbox',
            'title' => 'Activate to always load the full chapter',
            'defaultValue' => 'checked'
        ],
        'hide_title' => [
            'name' => 'Hide title of the chapter',
            'type' => 'checkbox',
            'title' => 'Activate to hide the title of the chapter and just show the number'
        ]
    ]];
    const CACHE_TIMEOUT = 60 * 15;

    public function collectData()
    {
        $manga = $this->getInput('manga');
        $html = getSimpleHTMLDOMCached($this->getURI() . $manga);
        $html = defaultLinkTo($html, $this->getURI());
        $full_chapter = $this->getInput('full_chapter');

        $chapter = $html->find('a.block.border.border-border.bg-card.mb-3.p-3.rounded', 0);

        $item = [];
        $item['title'] = $this->getInput('hide_title') ? $chapter->find('div.text-lg.font-bold', 0)->plaintext : $chapter->find('div.text-gray-500', 0)->plaintext;
        $item['uri'] = $chapter->href;
        $item['uid'] = $chapter->href;


        if ($full_chapter) {
            $item['content'] = $this->getFullChapter($item['uri']);
        } else {
            $item['content'] = <<<EOD
                <a href="{$item['uri']}" rel="nofollow noreferrer">Read chapter</a>
            EOD;
            ;
        }
        $this->items[] = $item;
    }

    private function getFullChapter($uri)
    {
        $html = getSimpleHTMLDOMCached($uri);
        $pictures = $html->find('picture.fixed-ratio');
        $img_html = '';

        foreach ($pictures as $picture) {
            $img_html .= <<<EOD
                <img src="{$picture->find('img.fixed-ratio-content', 0)->src}">
            EOD;
        }
        return $img_html;
    }

    public function getName()
    {
        if (!is_null($this->getKey('manga'))) {
            return $this->getKey('manga') . ' | ' . self::NAME;
        }

        return self::NAME;
    }

    public function getIcon()
    {
        return $this->getURI() . '/files/favicon-32x32.png';
    }
}