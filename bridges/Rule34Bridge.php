<?php
class Rule34ApiBridge extends BridgeAbstract {

	const MAINTAINER = 'mitsukarenai';
	const NAME = 'Rule34Api';
	const URI = 'https://api.rule34.xxx/';
	const DESCRIPTION = 'Returns images from given page';

	const PARAMETERS = array(
		'global' => array(
			'p' => array(
				'name' => 'page',
				'defaultValue' => 1,
				'type' => 'number'
			),
			't' => array(
				'name' => 'tags',
				'exampleValue' => 'pinup',
				'title' => 'Tags to search for'
			),
			'l' => array(
				'name' => 'limit',
				'defaultValue' => 100,
				'exampleValue' => 100,
				'title' => 'How many posts to retrieve (hard limit of 1000)'
			)
		),
		0 => array()
	);

	protected function getFullURI(){
		return $this->getURI()
		. 'index.php?&page=dapi&s=post&q=index&json=1&pid=' . $this->getInput('p')
		. '&limit=' . $this->getInput('l')
		. '&tags=' . urlencode($this->getInput('t'));
	}

	protected function getItemFromElement($element){
		//Debug::log('Element id: ' . $element->id);

		$item = array();
		$item['uri'] = 'https://rule34.xxx/index.php?page=post&s=view&id='
		. $element->id;
		$item['postid'] = $element->id;
		$item['author'] = $element->owner;
		$item['timestamp'] = date('d F Y H:i:s', $element->change);
		$thumbnailUri = $element->preview_url;
		$item['tags'] = $element->tags;
		$item['title'] = $this->getName() . ' | ' . $item['postid'];

		$item['content'] = '<a href="' . $item['uri'] . '"><img src="'
		. $thumbnailUri	. '" /></a><br><br><b>Tags:</b> '
		. $item['tags'] . '<br><br>' . $item['timestamp'];

		return $item;
	}

	public function collectData(){
		$content = getContents($this->getFullURI());

		//Debug::log('$content ' . $content);

		$posts = json_decode($content);

		//Debug::log('$posts ' . $content);

		foreach($posts as $post) {
			$this->items[] = $this->getItemFromElement($post);
		}
	}
}
