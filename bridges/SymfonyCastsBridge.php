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
        $url = 'https://symfonycasts.com/updates/find';
        $html = getSimpleHTMLDOM($url);

        /** @var simple_html_dom_node[] $dives */
        $dives = $html->find('div.user-notification-not-viewed');

        foreach ($dives as $div) {
            $type = $div->find('h5', 0);
            $title = $div->find('a', 0);
            $dateString = $div->find('h5.font-gray', 0);
            $href = $div->find('a', 0);
            $hrefAttribute = $href->getAttribute('href');
            $url = 'https://symfonycasts.com' . $hrefAttribute;

            $item = [];
            $item['uid'] = $div->getAttribute('data-mark-update-update-url-value');
            $item['title'] = $title->innertext;

            // this natural language date string does not work
            $item['timestamp'] = $dateString->innertext;

            $item['content'] = $type->plaintext . '<a href="' . $url . '">' . $title . '</a>';
            $item['uri'] = $url;
            $this->items[] = $item; // Add item to the list
        }
    }
}
