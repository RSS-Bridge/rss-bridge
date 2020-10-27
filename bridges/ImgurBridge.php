<?php
class ImgurBridge extends BridgeAbstract {

	const MAINTAINER = 'joshcoales';
	const NAME = 'Imgur Bridge';
	const URI = 'https://imgur.com/';
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'Input a search term or tag.';

	const PARAMETERS = array(
		'Search' => array(
			'q' => array(
				'name' => 'Search query',
				'required' => true
			) // Could be expanded with file type, or image size
		)
	);

	public function collectData()
	{
		// TODO: Implement collectData() method.
	}
}
