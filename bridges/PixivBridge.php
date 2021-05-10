<?php
class PixivBridge extends BridgeAbstract {

	const MAINTAINER = 'Yaman Qalieh';
	const NAME = 'Pixiv Bridge';
	const URI = 'https://www.pixiv.net/';
	const DESCRIPTION = 'Returns the tag search from pixiv.net';
	const CACHE_TIMEOUT = 21600; // 6h


	const PARAMETERS = array( array(
		'mode' => array(
			'name' => 'Post Type',
			'type' => 'list',
			'values' => array('Illustration' => 'illustrations/',
							  'Manga' => 'manga/',
							  'Novel' => 'novels/')
		),
		'tag' => array(
			'name' => 'Query to search',
			'exampleValue' => '葬送のフリーレン',
			'required' => true
		),
		'posts' => array(
			'name' => 'Post Limit',
			'type' => 'number',
			'defaultValue' => '10'
		),
		'fullsize' => array(
			'name' => 'Full-size Image',
			'type' => 'checkbox'
		)
	));

	const JSON_KEY_MAP = array(
		'illustrations/' => 'illust',
		'manga/' => 'manga',
		'novels/' => 'novel'
	);
	const WORK_LINK_MAP = array(
		'illustrations/' => 'artworks/',
		'manga/' => 'artworks/',
		'novels/' => 'novel/show.php?id='
	);

	public function collectData() {
		$content = getContents($this->getSearchURI())
				 or returnClientError('Unable to query pixiv.net');
		$content = json_decode($content, true);

		$key = self::JSON_KEY_MAP[$this->getInput('mode')];
		$count = 0;
		foreach($content['body'][$key]['data'] as $result) {
			$count++;
			if ($count > $this->getInput('posts')) {
				break;
			}

			$item = array();
			$item['id'] = $result['id'];
			$item['uri'] = 'https://www.pixiv.net/' . self::WORK_LINK_MAP[$this->getInput('mode')] . $result['id'];
			$item['title'] = $result['title'];
			$item['author'] = $result['userName'];
			$item['timestamp'] = $result['updateDate'];
			$item['content'] = "<img src='" . $this->cacheImage($result['url'], $item['id']) . "' />";

			$this->items[] = $item;
		}
	}

	private function getSearchURI() {
		$query = urlencode($this->getInput('tag'));

		$uri = static::URI . 'ajax/search/' . $this->getInput('mode')
			 . $query . '?word=' . $query . '&order=date_d&mode=all&p=1';

		return $uri;
	}

	private function cacheImage($url, $illustId) {
		$originalurl = $url;

		if ($this->getInput('fullsize')) {

			$url = preg_replace(array(0 => '/pximg\.net\/c\/250x250_80_a2\/.*\/img/m',
							   1 => '/p0_.*\./m'),
						 array(0 => 'pximg.net/img-original/img',
							   1 => 'p0.'),
						 $url);
			$illustId .= '_fullsize';
		}
		$path = PATH_CACHE . 'pixiv_img/';

		if(!is_dir($path))
			mkdir($path, 0755, true);

		if(!is_file($path . '/' . $illustId . '.jpg')) {
			$headers = array('Referer:  https://www.pixiv.net/');
			try {
				$illust = getContents($url, $headers);
			} catch (Exception $e) {
				$illust = getContents($originalurl, $headers);
			}
			file_put_contents($path . '/' . $illustId . '.jpg', $illust);
		}

		return 'cache/pixiv_img/' . $illustId . '.jpg';
	}
}
