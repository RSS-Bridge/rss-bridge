<?php

class NasaApodBridge extends FeedExpander
{
    const MAINTAINER = 'corenting';
    const NAME = 'NASA APOD';
    const URI = 'https://science.nasa.gov/apod/';
    const CACHE_TIMEOUT = 43200; // 12h
    const DESCRIPTION = 'Returns the 3 latest NASA APOD pictures and explanations';

    public function collectData()
    {
        $this->collectExpandableDatas('https://science.nasa.gov/feed/apod-basic/', 3);
    }

    protected function parseItem(array $item)
    {
        if (!isset($item['apod'])) {
            return null;
        }
        $apod = $item['apod'];

        // Prepare image hyperlink, if present
        $media = '';
        if (!empty($apod['hdurl'])) {
            $imageUri = e($apod['hdurl']);
            $imageAlt = e($apod['alt'] ?? '');
            $media = sprintf(
                '<a href="%s"><img src="%s" alt="%s" /></a><br />',
                $imageUri,
                $imageUri,
                $imageAlt
            );
        }

        // Prepare explanation and remove duplicated "Explanation:" prefix
        $explanation = preg_replace(
            '/\A(<strong>Explanation:\s*<\/strong>\s*)<b>Explanation<\/b>\s*:\s*/iu',
            '$1',
            $apod['explanation'] ?? ''
        );

        $item['content'] = $media . $explanation;
        return $item;
    }

    public function getName()
    {
        return self::NAME;
    }

    public function getURI()
    {
        return self::URI;
    }
}
