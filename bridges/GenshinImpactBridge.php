<?php

class GenshinImpactBridge extends BridgeAbstract
{
    const NAME = 'Genshin Impact';
    const URI = 'https://genshin.hoyoverse.com/en/news';
    const CACHE_TIMEOUT = 18000; // 5h
    const DESCRIPTION = 'Latest news from the Genshin Impact website';
    const MAINTAINER = 'Miicat_47';
    const PARAMETERS = [
        [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Latest' => 10,
                    'Info' => 11,
                    'Updates' => 12,
                    'Events' => 13
                ],
                'defaultValue' => 10
            ]
        ]
    ];

    public function collectData()
    {
        $url = 'https://api-os-takumi-static.hoyoverse.com/content_v2_user/app/a1b1f9d3315447cc/getContentList?iAppId=32&iChanId=395&iPageSize=5&iPage=1&sLangKey=en-us';
        $api_response = getContents($url);
        $json_list = Json::decode($api_response);

        foreach ($json_list['data']['list'] as $json_item) {
            $article_html = str_get_html($json_item['sContent']);

            // Check if article contains a embed YouTube video
            $exp_youtube = '#https://[w\.]+youtube\.com/embed/([\w]+)#m';
            if (preg_match($exp_youtube, $article_html, $matches)) {
                // Replace the YouTube embed with a YouTube link
                $yt_embed = $article_html->find('div[class="ttr-video-frame"]', 0);
                $yt_link = sprintf('<a href="https://youtube.com/watch?v=%1$s">https://youtube.com/watch?v=%1$s</a>', $matches[1]);
                $article_html = str_replace($yt_embed, $yt_link, $article_html);
            }
            $item = [];
            $item['title'] = $json_item['sTitle'];
            $item['timestamp'] = $json_item['dtStartTime'];
            $item['content'] = $article_html;
            $item['uri'] = 'https://genshin.hoyoverse.com/en/news/detail/' . $json_item['iInfoId'];
            $item['id'] = $json_item['iInfoId'];

            // Picture
            $json_ext = Json::decode($json_item['sExt']);
            $item['enclosures'] = [$json_ext['banner'][0]['url']];

            $this->items[] = $item;
        }
    }

    public function getIcon()
    {
        return 'https://genshin.hoyoverse.com/favicon.ico';
    }
}
