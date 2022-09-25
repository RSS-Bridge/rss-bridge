<?php

class EpicgamesBridge extends BridgeAbstract
{
    const NAME = 'Epic Games Store News';
    const MAINTAINER = 'otakuf';
    const URI = 'https://www.epicgames.com';
    const DESCRIPTION = 'Returns the latest posts from epicgames.com';
    const CACHE_TIMEOUT = 3600; // 60min

    const PARAMETERS = [ [
        'postcount' => [
            'name' => 'Limit',
            'type' => 'number',
            'required' => true,
            'title' => 'Maximum number of items to return',
            'defaultValue' => 10,
        ],
        'language' => [
            'name' => 'Language',
            'type' => 'list',
            'values' => [
                'English' => 'en',
                'العربية' => 'ar',
                'Deutsch' => 'de',
                'Español (Spain)' => 'es-ES',
                'Español (LA)' => 'es-MX',
                'Français' => 'fr',
                'Italiano' => 'it',
                '日本語' => 'ja',
                '한국어' => 'ko',
                'Polski' => 'pl',
                'Português (Brasil)' => 'pt-BR',
                'Русский' => 'ru',
                'ไทย' => 'th',
                'Türkçe' => 'tr',
                '简体中文' => 'zh-CN',
                '繁體中文' => 'zh-Hant',
             ],
            'title' => 'Language of blog posts',
            'defaultValue' => 'en',
        ],
    ]];

    public function collectData()
    {
        $api = 'https://store-content.ak.epicgames.com/api/';

        // Get sticky posts first
        // Example: https://store-content.ak.epicgames.com/api/ru/content/blog/sticky?locale=ru
        $urlSticky = $api . $this->getInput('language') . '/content/blog/sticky';
        // Then get posts
        // Example: https://store-content.ak.epicgames.com/api/ru/content/blog?limit=25
        $urlBlog = $api . $this->getInput('language') . '/content/blog?limit=' . $this->getInput('postcount');

        $dataSticky = getContents($urlSticky);
        $dataBlog = getContents($urlBlog);

        // Merge data
        $decodedData = array_merge(json_decode($dataSticky), json_decode($dataBlog));

        foreach ($decodedData as $key => $value) {
            $item = [];
            $item['uri'] = self::URI . $value->url;
            $item['title'] = $value->title;
            $item['timestamp'] = $value->date;
            $item['author'] = 'Epic Games Store';
            if (!empty($value->author)) {
                $item['author'] = $value->author;
            }
            if (!empty($value->content)) {
                $item['content'] = defaultLinkTo($value->content, self::URI);
            }
            if (!empty($value->image)) {
                $item['enclosures'][] = $value->image;
            }
            $item['uid'] = $value->_id;
            $item['id'] = $value->_id;

            $this->items[] = $item;
        }

        // Sort data
        usort($this->items, function ($item1, $item2) {
            if ($item2['timestamp'] == $item1['timestamp']) {
                return 0;
            }
            return ($item2['timestamp'] < $item1['timestamp']) ? -1 : 1;
        });

        // Limit data
        $this->items = array_slice($this->items, 0, $this->getInput('postcount'));
    }
}
