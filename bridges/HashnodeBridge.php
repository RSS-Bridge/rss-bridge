<?php
class HashnodeBridge extends BridgeAbstract {

	const MAINTAINER = 'liamka';
	const NAME = 'Hashnode';
	const URI = 'https://hashnode.com';
	const URI_ITEM = 'https://hashnode.com';
	const CACHE_TIMEOUT = 43200; // 12hr
	const DESCRIPTION = 'See trending or latest posts in Hashnode community.';
	const PARAMETERS = array( array(
		// 'sort' => array(
		// 	'name' => 'Filter items by',
		// 	'type' => 'list',
		// 	'required' => false,
		// 	'values' => array(
		// 		'Recent' => 'latest',
		// 		'Trending' => 'explore',
		// 	),
		// 	'defaultValue' => 'latest'
		// )
	));
	const LATEST_POSTS = 'https://hashnode.com/api/stories/recent?page=';

	public function collectData(){
		$url = self::URI . '/' . $this->getInput('sort');

		$html = getSimpleHTMLDOM($url)
			or returnServerError('Error while downloading the website content');

		$posts = $this->getRecentPosts();

		$this->items = $posts;
	}

	public function getRecentPosts(){
		$posts = [];
		for ($i=0; $i < 1; $i++) {
			$url = self::LATEST_POSTS . $i;
			$content = file_get_contents($url);
			$array = json_decode($content, true);

			if($array['posts'] != null) {
				foreach($array['posts'] as $post) {
					$item = [];
					$item['title'] = $post['title'];
					$item['content'] = $post['brief'];
					$item['timestamp'] = time();
					if($post['partOfPublication'] === true) {
						$item['uri'] = vsprintf("https://%s.hashnode.dev/%s", [$post['publication']['username'], $post['slug']]);
					} else {
						$item['uri'] = vsprintf("https://hashnode.com/post/%s", [$post['slug']]);
					}
					$posts[] = $item;
				}
			}
		}
		return $posts;
	}

	public function getTrendingPosts(){
		return [];
	}

	public function getName(){

		switch ($this->getInput('sort')) {
		    case 'explore':
		        return self::NAME . ': Tranding posts';
		        break;
		    default:
		       	return self::NAME . ': Recent posts';
		}
	}
}
