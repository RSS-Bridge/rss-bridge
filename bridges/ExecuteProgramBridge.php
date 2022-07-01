<?php

class ExecuteProgramBridge extends BridgeAbstract
{
    const NAME = 'Execute Program Blog';
    const URI = 'https://www.executeprogram.com/blog';
    const DESCRIPTION = 'Unofficial feed for the www.executeprogram.com blog';
    const MAINTAINER = 'dvikan';

    public function collectData()
    {
        $data = json_decode(getContents('https://www.executeprogram.com/api/pages/blog'));

        foreach ($data->posts as $post) {
            $year = $post->date->year;
            $month = $post->date->month;
            $day = $post->date->day;

            $item = [];
            $item['uri'] = sprintf('https://www.executeprogram.com/blog/%s', $post->slug);
            $item['title'] = $post->title;
            $dateTime = \DateTime::createFromFormat('Y-m-d', $year . '-' . $month . '-' . $day);
            $item['timestamp'] = $dateTime->format('U');
            $item['content'] = $post->body;

            $this->items[] = $item;
        }

        usort($this->items, function ($a, $b) {
            return $a['timestamp'] < $b['timestamp'];
        });
    }

    public function getIcon()
    {
        return 'https://www.executeprogram.com/favicon.ico';
    }
}
