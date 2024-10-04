<?php

class ForensicArchitectureBridge extends BridgeAbstract
{
    const NAME = 'Forensic Architecture';
    const URI = 'https://forensic-architecture.org/';
    const DESCRIPTION = 'Generates content feeds from forensic-architecture.org';
    const MAINTAINER = 'tillcash';

    public function collectData()
    {
        $url = 'https://forensic-architecture.org/api/fa/v1/investigations';
        $jsonData = json_decode(getContents($url));

        foreach ($jsonData->investigations as $investigation) {
            $this->items[] = [
                'content' => $investigation->abstract,
                'timestamp' => $investigation->publication_date,
                'title' => $investigation->title,
                'uid' => $investigation->id,
                'uri' => self::URI . 'investigation/' . $investigation->slug,
            ];
        }
    }
}
