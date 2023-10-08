<?php

class FiaBridge extends BridgeAbstract
{
    const NAME = 'Federation Internationale de l\'Automobile site feed';
    const URI = 'https://fia.com';
    const DESCRIPTION = 'Get the latest F1 documents from the fia site';
    const PARAMETERS = [];
    const CACHE_TIMEOUT = 900;

    public function collectData()
    {
        $url = 'https://www.fia.com/documents/championships/fia-formula-one-world-championship-14/';
        $html = getSimpleHTMLDOM($url);
        $items = $html->find('li.document-row');
        foreach ($items as $item) {
            /** @var simple_html_dom $item */
            // Do something with each list item
            $title = trim($item->find('div.title', 0)->plaintext);
            $href = $item->find('a', 0)->href;
            $url = 'https://www.fia.com' . $href;

            $date = $item->find('span.date-display-single', 0)->plaintext;

            $item = [];
            $item['uri'] = $url;
            $item['title'] = $title;
            $item['timestamp'] = (string) DateTime::createFromFormat('d.m.y H:i', $date)->getTimestamp();
            ;
            $item['author'] = 'Fia';
            $item['content'] = "Document on date $date: $title <br /><a href='$url'>$url</a>";
            $item['categories'] = 'Document';
            $item['uid'] = $title . $date;

            $count = count($this->items);
            if ($count > 20) {
                break;
            } else {
                $this->items[] = $item;
            }
        }
    }
}
