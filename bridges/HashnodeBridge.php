<?php
class HashnodeBridge extends BridgeAbstract {

	const MAINTAINER = 'liamka';
	const NAME = 'Hashnode';
	const URI = 'https://hashnode.com';
	const URI_ITEM = 'https://hashnode.com';
	const CACHE_TIMEOUT = 43200; // 12hr
	const DESCRIPTION = 'See trending or latest posts in Hashnode community.';
	const PARAMETERS = array();
	const LATEST_POSTS = 'https://hashnode.com/api/stories/recent?page=';

	public function collectData(){
		$url = self::URI . '/' . $this->getInput('sort');

		$html = getSimpleHTMLDOM($url)
			or returnServerError('Error while downloading the website content');

		$this->items = array();
		for ($i = 0; $i < 10; $i++) {
			$url = self::LATEST_POSTS . $i;
			$content = file_get_contents($url);
			$array = json_decode($content, true);

			if($array['posts'] != null) {
				foreach($array['posts'] as $post) {
					$item = array();
					$item['title'] = $post['title'];
					$item['content'] = $post['brief'];
					$item['timestamp'] = time();
					if($post['partOfPublication'] === true) {
						$item['uri'] = vsprintf('https://%s.hashnode.dev/%s', array($post['publication']['username'], $post['slug']));
					} else {
						$item['uri'] = vsprintf('https://hashnode.com/post/%s', array($post['slug']));
					}
					$this->items[] = $item;
				}
			}
		}
		$this->items = $posts;
	}

	public function getName(){
		return self::NAME . ': Recent posts';
	}
}
