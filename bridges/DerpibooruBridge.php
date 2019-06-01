<?php
class DerpibooruBridge extends BridgeAbstract {
	const NAME = 'Derpibooru Bridge';
	const URI = 'https://derpibooru.org/';
	const DESCRIPTION = 'Returns newest posts from a Derpibooru search';
	const CACHE_TIMEOUT = 300; // 5min
	const MAINTAINER = 'Roliga';

	const PARAMETERS = array(
		array(
			'f' => array(
				'name' => 'Filter',
				'type' => 'list',
				'values' => array(
					'Everything' => 56027,
					'18+ R34' => 37432,
					'Legacy Default' => 37431,
					'18+ Dark' => 37429,
					'Maximum Spoilers' => 37430,
					'Default' => 100073
				),
				'defaultValue' => 56027

			),
			'q' => array(
				'name' => 'Query',
				'required' => true
			)
		)
	);

	public function detectParameters($url){
		$params = array();

		// Search page e.g. https://derpibooru.org/search?q=cute
		$regex = '/^(https?:\/\/)?(www\.)?derpibooru.org\/search.+q=([^\/&?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['q'] = urldecode($matches[3]);
			return $params;
		}

		// Tag page, e.g. https://derpibooru.org/tags/artist-colon-devinian
		$regex = '/^(https?:\/\/)?(www\.)?derpibooru.org\/tags\/([^\/&?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['q'] = str_replace('-colon-', ':', urldecode($matches[3]));
			return $params;
		}

		return null;
	}

	public function getName(){
		if(!is_null($this->getInput('q'))) {
			return 'Derpibooru search for: '
				. $this->getInput('q');
		} else {
			return parent::getName();
		}
	}

	public function getURI(){
		if(!is_null($this->getInput('f')) && !is_null($this->getInput('q'))) {
		return self::URI
			. 'search?filter_id='
			. urlencode($this->getInput('f'))
			. '&q='
			. urlencode($this->getInput('q'));
		} else {
			return parent::getURI();
		}
	}

	public function collectData(){
		$queryJson = json_decode(getContents(
			self::URI
			. 'search.json?filter_id='
			. urlencode($this->getInput('f'))
			. '&q='
			. urlencode($this->getInput('q'))
		)) or returnServerError('Failed to query Derpibooru');

		foreach($queryJson->search as $post) {
			$item = array();

			$postUri = self::URI . $post->id;

			$item['uri'] = $postUri;
			$item['title'] = $post->id;
			$item['timestamp'] = strtotime($post->created_at);
			$item['author'] = $post->uploader;
			$item['enclosures'] = array('https:' . $post->image);
			$item['categories'] = explode(', ', $post->tags);

			$item['content'] = '<p><a href="' // image preview
				. $postUri
				. '"><img src="https:'
				. $post->representations->medium
				. '"></a></p><p>' // description
				. $post->description
				. '</p><p><b>Size:</b> ' // image size
				. $post->width
				. 'x'
				. $post->height
				. '<br><b>Source:</b> <a href="' // source link
				. $post->source_url
				. '">'
				. $post->source_url
				. '</a></p>';

			$this->items[] = $item;
		}
	}
}
