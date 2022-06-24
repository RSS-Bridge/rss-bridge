<?php

class ParksOnTheAirBridge extends BridgeAbstract
{
    const MAINTAINER = 's0lesurviv0r';
    const NAME = 'Parks On The Air Spots';
    const URI = 'https://pota.app/#';
    const API_URI = 'https://api.pota.app/spot/activator';
    const CACHE_TIMEOUT = 60; // 1m
    const DESCRIPTION = 'Parks On The Air Activator Spots';

    public function collectData()
    {

        $header = array('Content-type:application/json');
        $opts = array(CURLOPT_HTTPGET => 1);
        $json = getContents(self::API_URI, $header, $opts);

        $spots = json_decode($json, true);

        foreach ($spots as $spot) {
            $title = $spot['activator'] . ' @ ' . $spot['reference'] . ' ' .
                $spot['frequency'] . ' kHz';
            $park_link = self::URI . '/park/' . $spot['reference'];

            $content = <<<EOL
<a href="{$park_link}">
{$spot['reference']}, {$spot['name']}</a><br />
Location: {$spot['locationDesc']}<br />
Frequency: {$spot['frequency']} kHz<br />
Spotter: {$spot['spotter']}<br />
Comments: {$spot['comments']}
EOL;

            $this->items[] = array(
                'uri' => $park_link,
                'title' => $title,
                'content' => $content,
                'timestamp' => $spot['spotTime']
            );
        }
    }
}
