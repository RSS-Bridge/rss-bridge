<?php

class SymfonyCastsBridge extends BridgeAbstract
{
    const NAME = 'SymfonyCasts Bridge';
    const URI = 'https://symfonycasts.com/';
    const DESCRIPTION = 'Follow new updates on symfonycasts.com';
    const MAINTAINER = 'Park0';
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $html = getSimpleHTMLDOM('https://symfonycasts.com/updates/find');
        $dives = $html->find('div');

        /* @var simple_html_dom $div */
        foreach ($dives as $div) {
            $id = $div->getAttribute('data-mark-update-id-value');
            $type = $div->find('h5', 0);
            $title = $div->find('span', 0);
            $dateString = $div->find('h5.font-gray', 0);
            $href = $div->find('a', 0);
            $url = 'https://symfonycasts.com' . $href->getAttribute('href');

            $item = array(); // Create an empty item
            $item['uid'] = $id;
            $item['title'] = $title->innertext;
            $item['timestamp'] = $dateString->innertext;
            $item['content'] = $type->plaintext . '<a href="' . $url . '">' . $title . '</a>';
            $item['uri'] = $url;
            $this->items[] = $item; // Add item to the list
        }
    }
}
