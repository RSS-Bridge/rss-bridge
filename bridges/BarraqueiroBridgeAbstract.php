<?php

abstract class BarraqueiroBridgeAbstract extends BridgeAbstract
{
    const MANTAINER = 'FJSFerreira';

    public function collectDataBarraqueiro($base_uri, $full_uri)
    {
        $dom = getSimpleHTMLDOM($full_uri);

        $data = $dom->find('div.newsFundoGrey1, div.newsFundoGrey2');

        foreach ($data as $entry)
        {
            $item = [];

            $text = $entry->find('span.text', 0)->plaintext;
            
            $title = substr($text, 12);

            $day = substr($text, 0, 2);
            $month = substr($text, 3, 2);
            $year = substr($text, 6, 4);

            $item['uri'] = $base_uri . $entry->find('a', 0)->href;
            $item['title'] = $title;
            $item['timestamp'] = mktime(0, 0, 0, $month, $day, $year);

            $this->items[] = $item;
        }
    }
}

?>
