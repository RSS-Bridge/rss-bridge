<?php

class WhatsAppBlogBridge extends BridgeAbstract
{
    const NAME = 'WhatsApp Blog';
    const URI = 'https://blog.whatsapp.com/';
    const DESCRIPTION = 'WhatsApp Blog';
    const MAINTAINER = 'latz';
    const CACHE_TIMEOUT = 3600; // 1h

    public function collectData()
    {
        $html = file_get_html('https://blog.whatsapp.com/');

        $pattern = '/class=\\\\"_aof4\\\\">\\\\u003Cp>(.+?)\\\\u003C.+?Subject=(.+?)&amp;body=(.+?)(http:\\\\\/\\\\\/[^\\s"]+)/i';
        
        preg_match_all($pattern, $html, $matches);

        for ($i = 0; $i < count($matches[0]); $i++) {
            $date = html_entity_decode($matches[1][$i], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $subject = html_entity_decode($matches[2][$i], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $body = html_entity_decode($matches[3][$i], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $url = html_entity_decode($matches[4][$i], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $url = str_replace('\/', '/', $url);

            $item['title'] = $subject;
            $item['uri'] = $url;
            $item['timestamp'] = strtotime($date);
            $item['content'] = $body; // This isn't good HTML style, but at least syntactically correct
            $item['uid'] = md5($item['uri']);
            $this->items[] = $item;
        }
    }
}
