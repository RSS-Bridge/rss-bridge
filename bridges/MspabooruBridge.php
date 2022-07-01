<?php

class MspabooruBridge extends GelbooruBridge
{
    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Mspabooru';
    const URI = 'https://mspabooru.com/';
    const DESCRIPTION = 'Returns images from given page';

    protected function buildThumbnailURI($element)
    {
        return $this->getURI() . 'thumbnails/' . $element->directory
        . '/thumbnail_' . $element->image;
    }
}
