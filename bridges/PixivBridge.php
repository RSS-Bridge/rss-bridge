<?php
class PixivBridge extends BridgeAbstract {

	const MAINTAINER = 'teromene';
	const NAME = 'Pixiv Bridge';
	const URI = 'https://www.pixiv.net/';
	const DESCRIPTION = 'Returns the tag search from pixiv.net';


	const PARAMETERS = array( array(
		'tag' => array(
			'name' => 'Tag to search',
			'exampleValue' => 'example',
			'required' => true
		),
	));


	public function collectData(){

		$html = getContents(static::URI.'search.php?word=' . urlencode($this->getInput('tag')))
			or returnClientError('Unable to query pixiv.net');
		$regex = '/<input type="hidden"id="js-mount-point-search-result-list"data-items="([^"]*)/';
		$timeRegex = '/img\/([0-9]{4})\/([0-9]{2})\/([0-9]{2})\/([0-9]{2})\/([0-9]{2})\/([0-9]{2})\//';

		preg_match_all($regex, $html, $matches, PREG_SET_ORDER, 0);
		if(!$matches) return;

		$content = json_decode(html_entity_decode($matches[0][1]), true);
		$count = 0;
		foreach($content as $result) {
			if($count == 10) break;
			$count++;

			$item = array();
			$item['id'] = $result['illustId'];
			$item['uri'] = 'https://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $result['illustId'];
			$item['title'] = $result['illustTitle'];
			$item['author'] = $result['userName'];

			preg_match_all($timeRegex, $result['url'], $dt, PREG_SET_ORDER, 0);
			$elementDate = DateTime::createFromFormat('YmdHis',
						$dt[0][1] . $dt[0][2] . $dt[0][3] . $dt[0][4] . $dt[0][5] . $dt[0][6],
						new DateTimeZone('Asia/Tokyo'));
			$item['timestamp'] = $elementDate->getTimestamp();

			$item['content'] = "<img src='" . $this->cacheImage($result['url'], $item['id']) . "' />";
			$this->items[] = $item;
		}
	}

	private function cacheImage($url, $illustId) {

		$url = str_replace('_master1200', '', $url);
		$url = str_replace('c/240x240/img-master/', 'img-original/', $url);
		$path = CACHE_DIR . '/pixiv_img';

		if(!is_dir($path))
			mkdir($path, 0755, true);

		if(!is_file($path . '/' . $illustId . '.jpeg')) {
			$headers = array('Referer:  https://www.pixiv.net/member_illust.php?mode=medium&illust_id=' . $illustId);
			$illust = getContents($url, $headers);
			if(strpos($illust, '404 Not Found') !== false) {
				$illust = getContents(str_replace('jpg', 'png', $url), $headers);
			}
			file_put_contents($path . '/' . $illustId . '.jpeg', $illust);
		}

		return 'cache/pixiv_img/' . $illustId . '.jpeg';

	}

}
