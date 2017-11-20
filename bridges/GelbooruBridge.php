<?php
require_once('DanbooruBridge.php');

class GelbooruBridge extends DanbooruBridge {

	const MAINTAINER = 'mitsukarenai';
	const NAME = 'Gelbooru';
	const URI = 'http://gelbooru.com/';
	const DESCRIPTION = 'Returns images from given page';

	const PATHTODATA = '.thumb';
	const IDATTRIBUTE = 'id';
	const TAGATTRIBUTE = 'title';

	const PIDBYPAGE = 63;

	protected function getFullURI(){
		return $this->getURI()
		. 'index.php?page=post&s=list&pid='
		. ($this->getInput('p') ? ($this->getInput('p') - 1) * static::PIDBYPAGE : '')
		. '&tags=' . urlencode($this->getInput('t'));
	}

	protected function getTags($element){
		$tags = parent::getTags($element);
		$tags = explode(' ', $tags);

		// Remove statistics from the tags list (identified by colon)
		foreach($tags as $key => $tag) {
			if(strpos($tag, ':') !== false) unset($tags[$key]);
		}

		return implode(' ', $tags);
	}
}
