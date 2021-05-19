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
		$content = getContents($this->getSearchURI());
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
			$item['uri'] = static::URI . self::WORK_LINK_MAP[$this->getInput('mode')] . $result['id'];
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
		$illustId = preg_replace('/[^0-9]/', '', $illustId);
		$thumbnailurl = $url;

		$path = PATH_CACHE . 'pixiv_img/';
		if(!is_dir($path))
			mkdir($path, 0755, true);

		$path .= $illustId;
		if ($this->getInput('fullsize')) {
			$path .= '_fullsize';
		}
		$path .= '.jpg';

		if(!is_file($path)) {

			// Get fullsize URL
			if (!$this->getInput('mode') !== 'novels/' && $this->getInput('fullsize')) {
				$ajax_uri = static::URI . 'ajax/illust/' . $illustId;
				$imagejson = json_decode(getContents($ajax_uri), true);
				$url = $imagejson['body']['urls']['original'];
			}

			$headers = array('Referer: ' . static::URI);
			try {
				$illust = getContents($url, $headers);
			} catch (Exception $e) {
				$illust = getContents($thumbnailurl, $headers); // Original thumbnail
			}
			file_put_contents($path, $illust);
		}

		return 'cache/pixiv_img/' . preg_replace('/.*\//', '', $path);
	}
}
