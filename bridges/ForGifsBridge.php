<?php

class ForGifsBridge extends FeedExpander
{
    const MAINTAINER = 'logmanoriginal';
    const NAME = 'forgifs Bridge';
    const URI = 'https://forgifs.com';
    const DESCRIPTION = 'Returns the forgifs feed with actual gifs instead of images';

    public function collectData()
    {
        $this->collectExpandableDatas('https://forgifs.com/gallery/srss/7');
    }

    protected function parseItem($feedItem)
    {

        $item = parent::parseItem($feedItem);

        $content = str_get_html($item['content']);
        $img = $content->find('img', 0);
        $poster = $img->src;

        // The actual gif is the same path but its id must be decremented by one.
        // Example:
        // http://forgifs.com/gallery/d/279419-2/Reporter-videobombed-shoulder-checks.gif
        // http://forgifs.com/gallery/d/279418-2/Reporter-videobombed-shoulder-checks.gif
        // Notice how this changes ----------^
        // Now let's extract that number and do some math
        // Notice: Technically we could also load the content page but that would
        // require unnecessary traffic. As long as it works...
        $num = substr($img->src, 29, 6);
        $num -= 1;
        $img->src = substr_replace($img->src, $num, 29, strlen($num));
        $img->width = 'auto';
        $img->height = 'auto';

        $item['content'] = $content;

        return $item;
    }
}
