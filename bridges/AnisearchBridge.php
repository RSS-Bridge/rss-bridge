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
        ],
        'trailers' => [
            'name' => 'Trailers',
            'type' => 'checkbox',
            'title' => 'Will include trailes',
            'defaultValue' => false
        ]
    ]];

    public function collectData()
    {
        $baseurl = 'https://www.anisearch.de/';
        $trailers = false;
        $trailers = $this->getInput('trailers');
        $limit = 10;
        if ($trailers) {
            $limit = 5;
        }

        $dom = getSimpleHTMLDOM($this->getInput('category'));

        foreach ($dom->find('li.btype0') as $key => $li) {
            if ($key >= $limit) {
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

            foreach ($content->find('.hidden') as $element) {
                $element->remove();
            }

            //get trailer
            $ytlink = '';
            if ($trailers) {
                $trailerlink = $domarticle->find('section#trailers > div > div.swiper > ul.swiper-wrapper > li.swiper-slide > a', 0);
                if (isset($trailerlink)) {
                    $trailersite = getSimpleHTMLDOM($baseurl . $trailerlink->href);
                    $trailer = $trailersite->find('div#video > iframe', 0);
                    $trailer = $trailer->{'data-xsrc'};
                    $ytlink = <<<EOT
                        <br /><iframe width="560" height="315" src="$trailer" title="YouTube video player"
                        frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                    EOT;
                }
            }

            $this->items[] = [
                'title' => $title->plaintext,
                'uri' => $url,
                'content' => $headerimage . '<br />' . $content . $ytlink
            ];
        }
    }
}
