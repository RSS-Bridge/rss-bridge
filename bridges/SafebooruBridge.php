<?php

class SafebooruBridge extends GelbooruBridge
{
    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Safebooru';
    const URI = 'https://safebooru.org/';
    const DESCRIPTION = 'Returns images from given page';

    protected function buildThumbnailURI($element)
    {
        $regex = '/\.\w+$/';
        return $this->getURI() . 'thumbnails/' . $element->directory
        . '/thumbnail_' . preg_replace($regex, '.jpg', $element->image);
    }
}
