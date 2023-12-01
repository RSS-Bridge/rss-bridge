<?php

class FinanzflussBridge extends BridgeAbstract
{
    const MAINTAINER = 'Tone866';
    const NAME = 'finanzfluss Bridge';
    const URI = 'https://www.finanzfluss.de/blog';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Feed for finanzfluss';
    const LIMIT = 10;

    public function collectData()
    {
        $baseurl = 'https://www.finanzfluss.de';
        $dom = getSimpleHTMLDOM('https://www.finanzfluss.de/blog');
        foreach ($dom->find('.preview-card') as $li) {
            $a = $li->find('a', 0);
            $title = $a->find('.title', 0);
            $url = $baseurl . $a->href;

            //get article
            $domarticle = getSimpleHTMLDOM($url);
            $content = $domarticle->find('div.content', 0);

            //get header-image and set absolute src
            $headerimage = $domarticle->find('div.article-header-image', 0);
            $headerimageimg = $headerimage->find('img[src]', 0);
            $src = $headerimageimg->src;
            $headerimageimg->src = $baseurl . $src;
            $headerimageimg->srcset = $baseurl . $src;

            //set absolute src for all img
            foreach ($content->find('img[src]') as $img) {
                $src = $img->src;
                $img->src = $baseurl . $src;
                $img->srcset = $baseurl . $src;
            }

            //remove unwanted stuff
            foreach ($content->find('div.newsletter-signup') as $element) {
                $element->remove();
            }

            //get author
            $author = $domarticle->find('div.author-name', 0);

            $this->items[] = [
                'title' => $title->plaintext,
                'uri' => $url,
                'content' => $headerimage . '<br />' . $content,
                'author' => $author->plaintext
            ];
        }
    }
}
