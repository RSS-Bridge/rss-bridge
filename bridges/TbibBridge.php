<?php
require_once('GelbooruBridge.php');

class TbibBridge extends GelbooruBridge {

	const MAINTAINER = 'mitsukarenai';
	const NAME = 'Tbib';
	const URI = 'https://tbib.org/';
	const DESCRIPTION = 'Returns images from given page';

	protected function buildThumbnailURI($element){
		return $this->getURI() . 'thumbnails/' . $element->directory
		. '/thumbnail_' . substr($element->image, 0, -3) . 'jpg';
	}
}
