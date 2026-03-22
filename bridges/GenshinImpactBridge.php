<?php

class GenshinImpactBridge extends BridgeAbstract
{
    const NAME = 'Genshin Impact';
    const URI = 'https://genshin.hoyoverse.com/en/news';
    const CACHE_TIMEOUT = 18000; // 5h
    const DESCRIPTION = 'Latest news from the Genshin Impact website';
    const MAINTAINER = 'Miicat_47';

    const API_URL = 'https://api-os-takumi-static.hoyoverse.com/content_v2_user/app/a1b1f9d3315447cc/getContentList?iAppId=%u&iChanId=%u&iPageSize=%u&iPage=1&sLangKey=%s';
    // const API_URL = 'https://sg-public-api-static.hoyoverse.com/content_v2_user/app/a1b1f9d3315447cc/getContentList?iAppId=%u&iChanId=%u&iPageSize=%u&iPage=1&sLangKey=%s';
    const API_APP_ID = 32;

    const ARTICLE_URL = 'https://genshin.hoyoverse.com/%s/news/detail/%u';
    const FAVICON_URL = 'https://genshin.hoyoverse.com/favicon.ico';

    const CATEGORY_DEFAULT = 395;
    const LANGUAGE_DEFAULT = 'en-us';
    const LIMIT_MIN = 1;
    const LIMIT_DEFAULT = 5;
    const LIMIT_MAX = 100;

    const PARAMETERS = [
        [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Latest (all)' => 395,
                    'Info' => 396,
                    'Updates' => 397,
                    'Events' => 398
                ],
                'defaultValue' => self::CATEGORY_DEFAULT
            ],
            'limit' => [
                'name' => 'Limit',
                'type' => 'number',
                'defaultValue' => self::LIMIT_DEFAULT
            ],
            'language' => [
                'name' => 'Language',
                'type' => 'list',
                'values' => [
                    'Chinese' => 'zh-tw',
                    'English' => 'en-us',
                    'French' => 'fr-fr',
                    'German' => 'de-de',
                    'Indonesian' => 'id-id',
                    'Japanese' => 'ja-jp',
                    'Korean' => 'ko-kr',
                    'Portuguese' => 'pt-pt',
                    'Russian' => 'ru-ru',
                    'Spanish' => 'es-es',
                    'Thai' => 'th-th',
                    'Vietnamese' => 'vi-vn'
                ],
                'defaultValue' => self::LANGUAGE_DEFAULT
            ]
        ]
    ];

    public function collectData()
    {
        $category = $this->getInput('category') ?: self::CATEGORY_DEFAULT;
        $limit = $this->getInput('limit') ?: self::LIMIT_DEFAULT;
        $limit = min(self::LIMIT_MAX, max(self::LIMIT_MIN, $limit));
        $language = $this->getInput('language') ?: self::LANGUAGE_DEFAULT;

        $url = sprintf(self::API_URL, self::API_APP_ID, $category, $limit, $language);
        $api_response = getContents($url);
        $json_list = Json::decode($api_response);

        foreach ($json_list['data']['list'] as $json_item) {
            $article_html = str_get_html($json_item['sContent']);

            // Check if article contains a embed YouTube video
            $exp_youtube = '#https://[w\.]+youtube\.com/embed/([\w]+)#m';
            if (preg_match($exp_youtube, $article_html, $matches)) {
                // Replace the YouTube embed with a YouTube link
                $yt_embed = $article_html->find('div[class="ttr-video-frame"]', 0);
                $yt_embed->outertext = handleYoutube($yt_embed);
            }
            $item = [];
            $item['title'] = $json_item['sTitle'];
            $item['timestamp'] = $json_item['dtStartTime'];
            $item['content'] = $article_html;
            $item['uri'] = sprintf(self::ARTICLE_URL, $language, $json_item['iInfoId']);
            $item['id'] = $json_item['iInfoId'];

            // Picture
            $json_ext = Json::decode($json_item['sExt']);
            $item['enclosures'] = [$json_ext['banner'][0]['url']];

            $this->items[] = $item;
        }
    }

    public function getIcon()
    {
        return self::FAVICON_URL;
    }
}
