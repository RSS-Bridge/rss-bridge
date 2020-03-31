<?php
require_once('Shimmie2Bridge.php');

class Rule34pahealBridge extends Shimmie2Bridge {

	const MAINTAINER = 'mitsukarenai';
	const NAME = 'Rule34paheal';
	const URI = 'https://rule34.paheal.net/';
	const DESCRIPTION = 'Returns images from given page';

	protected function getItemFromElement($element){
		$item = array();
		$item['uri'] = $this->getURI() . $element->href;
		$item['id'] = (int)preg_replace('/[^0-9]/', '', $element->getAttribute(static::IDATTRIBUTE));
		$item['timestamp'] = time();
		$thumbnailUri = $element->find('img', 0)->src;
		$item['tags'] = $element->getAttribute('data-tags');
		$item['title'] = $this->getName() . ' | ' . $item['id'];
		$item['content'] = '<a href="'
		. $item['uri']
		. '"><img src="'
		. $thumbnailUri
		. '" /></a><br>Tags: '
		. $item['tags'];
		return $item;
	}
}
