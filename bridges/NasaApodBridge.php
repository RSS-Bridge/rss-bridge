<?php

class NasaApodBridge extends BridgeAbstract
{
    const MAINTAINER = 'corenting';
    const NAME = 'NASA APOD Bridge';
    const URI = 'https://apod.nasa.gov/apod/';
    const CACHE_TIMEOUT = 43200; // 12h
    const DESCRIPTION = 'Returns the 3 latest NASA APOD pictures and explanations';

    public function collectData()
    {
        $html = getSimpleHTMLDOM(self::URI . 'archivepix.html');

        // Start at 1 to skip the "APOD Full Archive" on top of the page
        for ($i = 1; $i < 4; $i++) {
            $item = [];

            $uri_page = $html->find('a', $i + 3)->href;
            $uri = self::URI . $uri_page;
            $item['uri'] = $uri;

            $picture_html = getSimpleHTMLDOM($uri);
            $picture_html_string = $picture_html->innertext;

            //Extract image and explanation
            $image_wrapper = $picture_html->find('a', 1);
            $image_path = $image_wrapper->href;
            $img_placeholder = $image_wrapper->find('img', 0);
            $img_alt = $img_placeholder->alt;
            $img_style = $img_placeholder->style;
            $image_uri = self::URI . $image_path;
            $new_img_placeholder = "<img src=\"$image_uri\" alt=\"$img_alt\" style=\"$img_style\">";
            $media = "<a href=\"$image_uri\">$new_img_placeholder</a>";
            $explanation = $picture_html->find('p', 2)->innertext;

            //Extract date from the picture page
            $date = explode(' ', $picture_html->find('p', 1)->innertext);
            $item['timestamp'] = strtotime($date[4] . $date[3] . $date[2]);

            //Other informations
            $item['content'] = $media . '<br />' . $explanation;
            $item['title'] = $picture_html->find('b', 0)->innertext;
            $this->items[] = $item;
        }
    }
}
