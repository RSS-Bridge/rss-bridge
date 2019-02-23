<?php
class BandcampBridge extends BridgeAbstract {

	const MAINTAINER = 'sebsauvage';
	const NAME = 'Bandcamp Tag';
	const URI = 'https://bandcamp.com/';
	const CACHE_TIMEOUT = 600; // 10min
	const DESCRIPTION = 'New bandcamp release by tag';
	const PARAMETERS = array( array(
		'tag' => array(
			'name' => 'tag',
			'type' => 'text',
			'required' => true
		)
	));
	const IMGURI = 'https://f4.bcbits.com/';
	const IMGSIZE_300PX = 23;
	const IMGSIZE_700PX = 16;

	public function getIcon() {
		return 'https://s4.bcbits.com/img/bc_favicon.ico';
	}

	public function collectData(){
		$url = self::URI . 'api/hub/1/dig_deeper';
		$data = $this->buildRequestJson();
		$header = array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data)
		);
		$opts = array(
			CURLOPT_CUSTOMREQUEST => 'POST',
			CURLOPT_POSTFIELDS => $data
		);
		$content = getContents($url, $header, $opts)
			or returnServerError('Could not complete request to: ' . $url);

		$json = json_decode($content);

		if ($json->ok !== true) {
			returnServerError('Invalid response');
		}

		foreach ($json->items as $entry) {
			$url = $entry->tralbum_url;
			$artist = $entry->artist;
			$title = $entry->title;
			// e.g. record label is the releaser, but not the artist
			$releaser = $entry->band_name !== $entry->artist ? $entry->band_name : null;

			$full_title = $artist . ' - ' . $title;
			$full_artist = $artist;
			if (isset($releaser)) {
				$full_title .= ' (' . $releaser . ')';
				$full_artist .= ' (' . $releaser . ')';
			}
			$small_img = $this->getImageUrl($entry->art_id, self::IMGSIZE_300PX);
			$img = $this->getImageUrl($entry->art_id, self::IMGSIZE_700PX);

			$item = array(
				'uri' => $url,
				'author' => $full_artist,
				'title' => $full_title
			);
			$item['content'] = "<img src='$small_img' /><br/>$full_title";
			$item['enclosures'] = array($img);
			$this->items[] = $item;
		}
	}

	private function buildRequestJson(){
		$requestJson = array(
			'tag' => $this->getInput('tag'),
			'page' => 1,
			'sort' => 'date'
		);
		return json_encode($requestJson);
	}

	private function getImageUrl($id, $size){
		return self::IMGURI . 'img/a' . $id . '_' . $size . '.jpg';
	}

	public function getName(){
		if(!is_null($this->getInput('tag'))) {
			return $this->getInput('tag') . ' - Bandcamp Tag';
		}

		return parent::getName();
	}
}
