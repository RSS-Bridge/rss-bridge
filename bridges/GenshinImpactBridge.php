<?php

class GenshinImpactBridge extends BridgeAbstract
{
    const MAINTAINER = 'corenting';
    const NAME = 'Genshin Impact';
    const URI = 'https://genshin.mihoyo.com/en/news';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'News from the Genshin Impact website';
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
        $category = $this->getInput('category');

        $url = 'https://genshin.mihoyo.com/content/yuanshen/getContentList';
        $url = $url . '?pageSize=3&pageNum=1&channelId=' . $category;
        $api_response = getContents($url);
        $json_list = json_decode($api_response, true);

        foreach ($json_list['data']['list'] as $json_item) {
            $article_url = 'https://genshin.mihoyo.com/content/yuanshen/getContent';
            $article_url = $article_url . '?contentId=' . $json_item['contentId'];
            $article_res = getContents($article_url);
            $article_json = json_decode($article_res, true);
            $article_time = $article_json['data']['start_time'];
            $timezone = 'Asia/Shanghai';
            $article_timestamp = new DateTime($article_time, new DateTimeZone($timezone));

            $item = [];

            $item['title'] = $article_json['data']['title'];
            $item['timestamp'] = $article_timestamp->format('U');
            $item['content'] = $article_json['data']['content'];
            $item['uri'] = $this->getArticleUri($json_item);
            $item['id'] = $json_item['contentId'];

            // Picture
            foreach ($article_json['data']['ext'] as $ext) {
                if ($ext['arrtName'] == 'banner' && count($ext['value']) == 1) {
                    $item['enclosures'] = [$ext['value'][0]['url']];
                    break;
                }
            }

            $this->items[] = $item;
        }
    }

    public function getIcon()
    {
        return 'https://genshin.mihoyo.com/favicon.ico';
    }

    private function getArticleUri($json_item)
    {
        return 'https://genshin.mihoyo.com/en/news/detail/' . $json_item['contentId'];
    }
}
