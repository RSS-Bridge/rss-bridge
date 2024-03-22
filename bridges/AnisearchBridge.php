<?php

class AnisearchBridge extends BridgeAbstract
{
    const MAINTAINER = 'Tone866';
    const NAME = 'Anisearch';
    const URI = 'https://www.anisearch.de';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Feed for Anisearch';
    const PARAMETERS = [[
        'category' => [
            'name' => 'Dub',
            'type' => 'list',
            'values' => [
                'DE'
                => 'https://www.anisearch.de/anime/index/page-1?char=all&synchro=de&sort=date&order=desc&view=4',
                'EN'
                => 'https://www.anisearch.de/anime/index/page-1?char=all&synchro=en&sort=date&order=desc&view=4',
                'JP'
                => 'https://www.anisearch.de/anime/index/page-1?char=all&synchro=ja&sort=date&order=desc&view=4'
            ]
        ]
    ]];

    public function collectData()
    {
        $baseurl = 'https://www.anisearch.de/';
        $limit = 10;
        $dom = getSimpleHTMLDOM($this->getInput('category'));
        foreach ($dom->find('li.btype0') as $key => $li) {
            if ($key > $limit) {
                break;
            }

            $a = $li->find('a', 0);
            $title = $a->find('span.title', 0);
            $url = $baseurl . $a->href;

            //get article
            $domarticle = getSimpleHTMLDOM($url);
            $content = $domarticle->find('div.details-text', 0);

            //get header-image and set absolute src
            $headerimage = $domarticle->find('img#details-cover', 0);
            $src = $headerimage->src;

            //remove unwanted stuff
            #foreach ($content->find('div.newsletter-signup') as $element) {
            #    $element->remove();
            #}

            $this->items[] = [
                'title' => $title->plaintext,
                'uri' => $url,
                'content' => $headerimage . '<br />' . $content
            ];
        }
    }
}
