<?php

abstract class BarraqueiroBridgeAbstract extends BridgeAbstract
{
    const MAINTAINER = 'FJSFerreira';

    public function collectDataBarraqueiro($base_uri, $full_uri)
    {
        $dom = getSimpleHTMLDOM($full_uri);

        $data = $dom->find('div.newsFundoGrey1, div.newsFundoGrey2');

        foreach ($data as $entry) {
            $item = [];

            $text = $entry->find('span.text', 0)->plaintext;

            $title = substr($text, 12);

            $item['uri'] = $base_uri . $entry->find('a', 0)->href;
            $item['title'] = $title;
            $item['timestamp'] = DateTimeImmutable::createFromFormat('d-m-Y+', $text)->format('Y-m-d');

            $this->items[] = $item;
        }
    }
}
