<?php

class XbooruBridge extends GelbooruBridge
{
    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Xbooru';
    const URI = 'https://xbooru.com/';
    const DESCRIPTION = 'Returns images from given page';

    protected function buildThumbnailURI($element)
    {
        return $this->getURI() . 'thumbnails/' . $element->directory
        . '/thumbnail_' . $element->hash . '.jpg';
    }
}
