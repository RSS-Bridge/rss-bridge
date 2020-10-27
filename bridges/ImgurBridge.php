<?php

class ImgurBridge extends BridgeAbstract
{

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

	public function getURI()
	{
		return self::URI
			. '/search/time/?q='
			. filter_var($this->getInput('g'), FILTER_SANITIZE_URL)
			. '&qs=list';
	}

	public function collectData()
	{
		$html = getSimpleHTMLDOM($this->getURI());

		$posts = $html->find('div.post-list')
		or returnServerError('Failed finding posts!');

		foreach ($posts as $post) {

			$item = array();

			$item['uri'] = $this::URI . $post->find('a', 0)->href;
			$item['title'] = $post->find('p.search-item-title', 0)->plaintext;
			$item['author'] = $post->find('div.post-byline a.account', 0)->plaintext;
			$item['content'] = '';  // TODO
			$item['timestamp'] = ''; // TODO
			$item['enclosures'] = ''; // TODO

			$this->items[] = $item;
		}
	}
}
