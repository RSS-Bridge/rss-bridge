<?php

class HonkaiImpactSeaBridge extends BridgeAbstract
{
    const MAINTAINER = 'hpacleb';
    const NAME = 'Honkai Impact SEA';
    const URI = 'https://honkaiimpact3.hoyoverse.com/asia/en-us/news';
    const CACHE_TIMEOUT = 7200; // 2h
    const DESCRIPTION = 'News from the Honkai Impact SEA website';
    const PARAMETERS = [
        [
            'category' => [
                'name' => 'Category',
                'type' => 'list',
                'values' => [
                    'Latest' => 403,
                    'Info' => 404,
                    'Updates' => 405,
                    'Events' => 406,
                    'Guides' => 407,
                    'Other' => 408
                ],
                'defaultValue' => 403
            ]
        ]
    ];

    public function collectData()
    {
        $category = $this->getInput('category');

        $url = 'https://sg-content-static-sea.hoyoverse.com/content/bh3Sea/getContentList';
        $url = $url . '?pageSize=10&pageNum=1&game_biz=bh3_os&channelId=' . $category;
        $api_response = getContents($url);
        $json_list = json_decode($api_response, true);

        foreach ($json_list['data']['list'] as $json_item) {
            $article_url = 'https://sg-content-static-sea.hoyoverse.com/content/bh3Sea/getContent?game_biz=bh3_os&';
            $article_url = $article_url . 'contentId=' . $json_item['contentId'];
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
                if ($ext['arrtName'] == '新闻封面' && count($ext['value']) == 1) {
                    $item['enclosures'] = [$ext['value'][0]['url']];
                    break;
                }
            }

            $this->items[] = $item;
        }
    }

    public function getIcon()
    {
        return 'https://honkaiimpact3.hoyoverse.com/favicon.ico';
    }

    private function getArticleUri($json_item)
    {
        return 'https://honkaiimpact3.hoyoverse.com/asia/en-us/news/' . $json_item['contentId'];
    }
}
