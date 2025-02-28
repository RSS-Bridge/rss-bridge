<?php

class LeagueOfLegendsNewsBridge extends BridgeAbstract
{
    const NAME = 'League of Legends News';
    const URI = 'https://www.leagueoflegends.com';
    const DESCRIPTION = 'Official League of Legends news.';
    const MAINTAINER = 'KappaPrajd';
    const PARAMETERS = [
        [
            'language' => [
                'name' => 'Language',
                'type' => 'list',
                'defaultValue' => 'en-us',
                'values' => [
                    'English (NA)' => 'en-us',
                    'English (EUW)' => 'en-gb',
                    'Deutsch' => 'de-de',
                    'Español (EUW)' => 'es-es',
                    'Français' => 'fr-fr',
                    'Italiano' => 'it-it',
                    'Polski' => 'pl-pl',
                    'Ελληνικά' => 'el-gr',
                    'Română' => 'ro-ro',
                    'Magyar' => 'hu-hu',
                    'Čeština' => 'cs-cz',
                    'Español (LATAM)' => 'es-mx',
                    'Português' => 'pt-br',
                    '日本語' => 'ja-jp',
                    'Русский' => 'ru-ru',
                    'Türkçe' => 'tr-tr',
                    'English (OCE)' => 'en-au',
                    '한국어' => 'ko-kr',
                    'English (SG)' => 'en-sg',
                    'English (PH)' => 'en-ph',
                    'Tiếng Việt' => 'vi-vn',
                    'ภาษาไทย' => 'th-th',
                    '繁體中文' => 'zh-tw',
                    'العربية' => 'ar-ae'
                ]
            ],
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'defaultValue' => 'all',
                'values' => [
                    'All' => 'all',
                    'Game updates' => 'game-updates',
                    'Esports' => 'esports',
                    'Dev' => 'dev',
                    'Lore' => 'lore',
                    'Media' => 'media',
                    'Merch' => 'merch',
                    'Community' => 'community',
                    'Riot Games' => 'riot-games'
                ]
            ],
            'onlyPatchNotes' => [
                'name' => 'Only patch notes',
                'type' => 'checkbox',
                'defaultValue' => false,
            ],
        ],

    ];

    public function collectData()
    {
        $siteUrl = $this->getSiteUrl();
        $html = getSimpleHTMLDOM($siteUrl);

        $articles = $html->find('a[data-testid=articlefeaturedcard-component]');

        foreach ($articles as $article) {
            $title = $article->find('div[data-testid=card-title]', 0)->plaintext;
            $content = $article->find('div[data-testid=card-description] div div div', 0)->plaintext;
            $timestamp = $article->find('div[data-testid=card-date] time', 0)->getAttribute('datetime');
            $href = $article->getAttribute('href');

            $item = [
                'title' => $title,
                'content' => $content,
                'timestamp' => $timestamp,
                'uri' => $this->getArticleUri($href),
            ];

            $this->items[] = $item;
        }
    }

    private function getSiteUrl()
    {
        $lang = $this->getInput('language');
        $category = $this->getInput('category');
        $onlyPatchNotes = $this->getInput('onlyPatchNotes');

        $url = self::URI . '/' . $lang . '/news';

        if ($onlyPatchNotes) {
            return $url . '/tags/patch-notes';
        } else if ($category === 'all') {
            return $url;
        }

        return $url . '/' . $category;
    }

    private function getArticleUri($href)
    {
        $isInternalLink = str_starts_with($href, '/');

        if ($isInternalLink) {
            return self::URI . $href;
        }

        return $href;
    }
}