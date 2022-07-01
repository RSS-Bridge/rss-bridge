<?php

class TbibBridge extends GelbooruBridge
{
    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Tbib';
    const URI = 'https://tbib.org/';
    const DESCRIPTION = 'Returns images from given page';

    protected function buildThumbnailURI($element)
    {
        $regex = '/\.\w+$/';
        return $this->getURI() . 'thumbnails/' . $element->directory
        . '/thumbnail_' . preg_replace($regex, '.jpg', $element->image);
    }
}
