<?php
class PocketExploreBridge extends BridgeAbstract {
    const NAME = 'Pocket Explore Bridge';
    const URI = 'https://getpocket.com/';
    const DESCRIPTION = 'Fetches recommendations from Pocket Explore (What you can see on the Firefox Home)';
    const MAINTAINER = 'dhuschde';
    const PARAMETERS = [[
	'language' => [
                'name' => 'Language',
                'required' => true,
                'exampleValue' => 'en',
	],
	'region' => [
                'name' => 'Region',
                'required' => true,
                'exampleValue' => 'US',
        ],
    ]];

    public function collectData() {
        $language = $this->getInput('language');
        $region = $this->getInput('region');

        $url = 'https://getpocket.cdn.mozilla.net/v3/firefox/global-recs?version=3&consumer_key=40249-e88c401e1b1f2242d9e441c4&locale_lang=' . $language . '&region=' . $region . '&count=30';

        $json = getContents($url);
        $data = json_decode($json, true);

        foreach ($data['recommendations'] as $recommendation) {
            $this->items[] = array(
                'title' => $recommendation['title'],
                'uri' => $recommendation['url'],
                'author' => $recommendation['domain'],
                'content' => "<img src='" . $recommendation['raw_image_src'] . "'></img><p>" . $recommendation['excerpt'] . "</p>",
            );
        }
    }
}
