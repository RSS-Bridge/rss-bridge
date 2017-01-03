<?php

class MixCloudBridge extends BridgeAbstract {

	const MAINTAINER = "Alexis CHEMEL";
	const NAME = "MixCloud";
	const URI = "https://mixcloud.com/";
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = "Returns latest musics on user stream";

    const PARAMETERS = array(array(
        'u' => array(
            'name' => 'username',
            'required' => true,
        )));

    public function getName(){

        return 'MixCloud - '.$this->getInput('u');
    }

    public function collectData() {

        $html = getSimpleHTMLDOM(self::URI.'/'.$this->getInput('u'))
            or returnServerError('Could not request MixCloud.');

        foreach($html->find('div.card-elements-container') as $element) {

            $item = array();

            $item['uri'] = self::URI.$element->find('h3.card-cloudcast-title a', 0)->getAttribute('href');
            $item['title'] = html_entity_decode($element->find('h3.card-cloudcast-title a span', 0)->getAttribute('title'), ENT_QUOTES);

            $image = $element->find('img.image-for-cloudcast', 0);

            if( $image ) {

            	$item['content'] = '<img src="'.$image->getAttribute('src').'" />';
            }

            $item['author'] = trim($element->find('h4.card-cloudcast-user a', 0)->innertext);

            $this->items[] = $item;
        }
    }
}
