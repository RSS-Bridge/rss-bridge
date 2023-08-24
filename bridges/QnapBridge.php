<?php

declare(strict_types=1);

final class QnapBridge extends BridgeAbstract
{
    const NAME = 'QNAP';
    const URI = 'https://www.qnap.com/fr-fr/security-news/2022';
    const DESCRIPTION = <<<'DESCRIPTION'
<b>Use offical feed instead: https://www.qnap.com/fr-fr/security-news/feed </b><br><br>
Unofficial feed for security news.
DESCRIPTION;

    const MAINTAINER = 'dvikan';

    public function collectData()
    {
        $thisYear = date('Y');
        $url = sprintf('https://www.qnap.com/api/v1/articles/security-news?locale=fr-fr&year=%s&page=1', $thisYear);
        $response = json_decode(getContents($url));
        foreach ($response->data as $post) {
            $item = [];
            $item['uri'] = sprintf('https://www.qnap.com%s', $post->url);
            $item['title'] = $post->title;
            $item['timestamp'] = \DateTime::createFromFormat('Y-m-d', $post->date)->format('U');
            $image = sprintf('<img src="https://www.qnap.com%s">', $post->image_url);
            $item['content'] = $image . '<br><br>' . $post->desc;
            $this->items[] = $item;
        }
        usort($this->items, function ($a, $b) {
            return $a['timestamp'] < $b['timestamp'];
        });
    }
}
