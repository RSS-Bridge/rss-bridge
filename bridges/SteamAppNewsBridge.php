<?php

class SteamAppNewsBridge extends BridgeAbstract
{
    const NAME = 'Steam App News';
    const URI = 'https://www.steamcommunity.com';
    const DESCRIPTION = 'Get the latest news for a game on Steam.';
    const MAINTAINER = 'otakuf';
    const CACHE_TIMEOUT = 3600; // 1h

    const PARAMETERS = [ [
        'appid' => [
            'name' => 'App ID',
            'title' => 'App ID (only digits). Find your App ID with steamdb.info',
            'type' => 'number',
            'exampleValue' => '730',
            'required' => true
        ],
        'maxlength' => [
            'name' => 'Max Length',
            'title' => 'Maximum length for the content to return, 0 for full content',
            'type' => 'number',
            'defaultValue' => 0
        ],
        'count' => [
            'name' => 'Count',
            'title' => '# of posts to retrieve (default 20)',
            'type' => 'number',
            'defaultValue' => 20
        ],
        'tags' => [
            'name' => 'Tag Filter',
            'title' => 'Comma-separated list of tags to filter by',
            'type' => 'text',
            'exampleValue' => 'patchnotes'
        ]
    ]];

    public function collectData()
    {
        $apiTarget = 'https://api.steampowered.com/ISteamNews/GetNewsForApp/v2/';
        // Example with params: https://api.steampowered.com/ISteamNews/GetNewsForApp/v2/?appid=730&maxlength=0&count=20
        // More info at dev docs https://partner.steamgames.com/doc/webapi/ISteamNews
        $url =
            $apiTarget
            . '?appid=' . $this->getInput('appid')
            . '&maxlength=' . $this->getInput('maxlength')
            . '&count=' . $this->getInput('count')
            . '&tags=' . $this->getInput('tags');

        // Get the JSON content
        $json = getContents($url);
        $json_list = json_decode($json, true);

        foreach ($json_list['appnews']['newsitems'] as $json_item) {
            $this->items[] = $this->collectArticle($json_item);
        }
    }

    private function collectArticle($json_item)
    {
        $item = [];
        $item['uri'] = preg_replace('[ ]', '%20', $json_item['url']);
        $item['title'] = $json_item['title'];
        $item['timestamp'] = $json_item['date'];
        $item['author'] = $json_item['author'];

        # Fix /n
        if (str_contains($item['uri'], 'steam_community_announcements')) {
            $item['content'] = $this->replaceBBcodes($json_item['contents']);
        } else {
            $item['content'] = $json_item['contents'];
        }
        $item['uid'] = $json_item['gid'];
        return $item;
    }

    private function replaceBBcodes($text)
    {
        //$text = strip_tags($text);
        $text = nl2br($text);
        // BBcode array, all list available: https://steamcommunity.com/comment/ForumTopic/formattinghelp
        $find = [
            '~\[h1\](.*?)\[/h1\]~s',
            '~\[h2\](.*?)\[/h2\]~s',
            '~\[h3\](.*?)\[/h3\]~s',
            '~\[list\](.*?)\[/list\]~s',
            '~\[olist\](.*?)\[/olist\]~s',
            '~\[\*\]~s',
            '~\[b\](.*?)\[/b\]~s',
            '~\[i\](.*?)\[/i\]~s',
            '~\[u\](.*?)\[/u\]~s',
            '~\[strike\](.*?)\[/strike\]~s',
            '~\[spoiler\](.*?)\[/spoiler\]~s',
            '~\[noparse\](.*?)\[/noparse\]~s',
            '~\[hr\]~s',
            '~\[quote\](.*?)\[/quote\]~s',
            '~\[code\](.*?)\[/code\]~s',
            '~\{STEAM_CLAN_IMAGE\}~s',
            '~\[url=([^"><]*?)\](.*?)\[/url\]~s',
            '~\[img\](https?://[^"><]*?\.(?:jpg|jpeg|gif|png|bmp))\[/img\]~s'
        ];
        // HTML tags to replace BBcode
        $replace = [
            '<h1>$1</h1>',
            '<h2>$1</h2>',
            '<h3>$1</h3>',
            '<ul>$1</ul>',
            '<ol>$1</ol>',
            '<li>',
            '<b>$1</b>',
            '<i>$1</i>',
            '<u>$1</u>',
            '<s>$1</s>',
            '$1', // Just remove spoiler
            '$1', // Just remove noparse
            '<hr>',
            '<blockquote>$1</blockquote>',
            '<code>$1</code>',
            'https://steamcdn-a.akamaihd.net/steamcommunity/public/images/clans',
            '<a href="$1">$2</a>',
            '<img src="$1" alt="" />'
        ];
        // Replacing the BBcodes with corresponding HTML tags
        return preg_replace($find, $replace, $text);
    }
}
