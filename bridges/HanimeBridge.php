<?php

class HanimeBridge extends BridgeAbstract
{
    const NAME = 'Hanime';
    const URI = 'https://hanime.tv';
    const DESCRIPTION = 'Return recent Hanime.tv hentai video uploads';
    const MAINTAINER = 'Miicat_47';

    public function collectData()
    {
        $html = getSimpleHTMLDOM('https://hanime.tv/')->find('.htv-carousel__scrolls', 0);
        $html = defaultLinkTo($html, $this->getURI());

        foreach ($html->find('.item') as $video) {
            $item = [];

            $video_uri = $video->find('.no-touch', 0)->href;

            // Get video cover url
            // Use regex to get video_uri title
            $exp = '/\/([A-Za-z\-0-9]+$)/m';
            preg_match_all($exp, $video_uri, $matches, PREG_SET_ORDER, 0);
            // Use the video title as name for the cover file
            $cover = 'https://cdn.statically.io/img/akidoo.top/images/covers/' . $matches[0][1] . '-cv1.png';

            $item['uri'] = $video_uri;
            $item['title'] = $video->find('.hv-title', 0)->plaintext;
            $item['content'] = sprintf('<img src="%s">', $cover);

            $this->items[] = $item;
        }
    }
}
