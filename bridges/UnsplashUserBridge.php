<?php

class UnsplashUserBridge extends BridgeAbstract
{

    const MAINTAINER = 'nel50n';
    const NAME = 'Unsplash User Bridge';
    const URI = 'https://unsplash.com/';
    const CACHE_TIMEOUT = 43200; // 12h
    const DESCRIPTION = 'Returns the latest photos from a specific user on Unsplash';

    const PARAMETERS = array(array(
        'u' => array(
            'name' => 'Username',
            'type' => 'text',
            'defaultValue' => ''
        ),
        'm' => array(
            'name' => 'Max number of photos',
            'type' => 'number',
            'defaultValue' => 20
        ),
        'w' => array(
            'name' => 'Width',
            'exampleValue' => '1920, 1680, …',
            'defaultValue' => '1920'
        ),
        'q' => array(
            'name' => 'JPEG quality',
            'type' => 'number',
            'defaultValue' => 75
        )
    ));

    public function collectData()
    {
        $username = $this->getInput('u');
        $width = $this->getInput('w');
        $max = $this->getInput('m');
        $quality = $this->getInput('q');

        // example: https://unsplash.com/napi/users/danielsessler/photos?page=1&per_page=5
        $api_response = getContents('https://unsplash.com/napi/users/' . $username . '/photos?page=1&per_page=' . $max)
        or returnServerError('Could not request Unsplash API.');
        $json = json_decode($api_response, true);

        foreach ($json as $json_item) {
            $item = array();

            // Get resized image URI
            $uri = $json_item['urls']['regular'] . '.jpg'; // '.jpg' only for format hint
            $uri = str_replace('q=80', 'q=' . $quality, $uri);
            $uri = str_replace('w=1080', 'w=' . $width, $uri);

            // link unsplash page of image
            $item['uri'] = $json_item['links']['html'];

            // Get title from description
            if (is_null($json_item['alt_description'])) {
                if (is_null($json_item['description'])) {
                    $item['title'] = 'Unsplash picture from ' . $json_item['user']['name'];
                } else {
                    $item['title'] = $json_item['description'];
                }
            } else {
                $item['title'] = $json_item['alt_description'];
            }

            $item['timestamp'] = time();
            $item['content'] =
                '<a href="' . $json_item['user']['links']['html'] . '">' . $username . '</a><br>'
                . '<a href="'
                . $uri
                . '"><img src="'
                . $json_item['urls']['small']
                . '" /></a>';

            $this->items[] = $item;
        }
    }
}