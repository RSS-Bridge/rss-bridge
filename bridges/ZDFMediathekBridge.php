<?php

declare(strict_types=1);

class ZDFMediathekBridge extends BridgeAbstract
{
    const NAME = 'ZDF-Mediathek Bridge';
    const URI = 'https://www.zdf.de/';
    const DESCRIPTION = 'Feed of any show,series,documentary etc. in the ZDF-Mediathek, specified by its path';
    const MAINTAINER = 'nabakolu';
    const PARAMETERS = [
    [
      'path' => [
        'name' => 'ZDF Show URL',
        'type' => 'text',
        'required' => true,
        'exampleValue' => 'https://www.zdf.de/magazine/zdfheute-live-102'
      ]
    ]
    ];

    public function collectData()
    {
        $url = $this->getInput('path');
        $data = $this->getJSON($url);

        foreach ($data['value']['data']['smartCollectionByCanonical']['seasons']['nodes'][0]['episodes']['nodes'] as $episode_node) {
            $item = [];
            $item['title'] = $episode_node['title'];
            $item['timestamp'] = strtotime($episode_node['editorialDate']);
            $item['uri'] = $episode_node['sharingUrl'];
            $item['uid'] = $episode_node['id'];

            $description = $episode_node['teaser']['description'];
            $image = $episode_node['teaser']['imageWithoutLogo']['layouts']['dim1920X1080'];
            $image_desc = $episode_node['teaser']['imageWithoutLogo']['altText'];

            $item['content'] = "<img src='{$image}' alt='$image_desc' /><p>{$description}</p>";

            $this->items[] = $item;
        }
    }

    private function getJSON($url)
    {
        $html = getContents($url);

      // Find all <script> tags in the HTML content
        preg_match_all('/<script.*?>(.*?)<\/script>/is', $html, $script_matches);

      // Contains json data
        $data = null;

        foreach ($script_matches[1] as $script_content) {
            if (strpos($script_content, 'self.__next_f.push([1,"{') === 0) {
                // Strip the 'self.__next_f.push([1,"{'
                $json_string = substr($script_content, 23);

                // Strip the trailing '"])'
                $json_string = substr($json_string, 0, -3);

                // Unescape \" and \\
                $json_string = stripslashes($json_string);

                $data = json_decode($json_string, true);

                break;
            }
        }
        return $data;
    }
}
