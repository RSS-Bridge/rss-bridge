<?php
class BandcampBridge extends BridgeAbstract {

	const MAINTAINER = 'sebsauvage';
	const NAME = 'Bandcamp Bridge';
	const URI = 'https://bandcamp.com/';
	const CACHE_TIMEOUT = 600; // 10min
	const DESCRIPTION = 'New bandcamp releases by tag or band';
	const PARAMETERS = array(
		'By tag' => array(
			'tag' => array(
				'name' => 'tag',
				'type' => 'text',
				'required' => true
			)
		),
		'By band' => array(
			'band' => array(
				'name' => 'band',
				'type' => 'text',
				'required' => true
			),
			'tracks' => array(
				'name' => 'new tracks',
				'type' => 'checkbox',
				'title' => 'Releases show up anew when new tracks are added',
				'defaultValue' => 'checked'
			),
			'limit' => array(
				'name' => 'limit',
				'type' => 'number',
				'title' => 'Number of releases to return',
				'defaultValue' => 5
			)
		)
	);
	const IMGURI = 'https://f4.bcbits.com/';
	const IMGSIZE_300PX = 23;
	const IMGSIZE_700PX = 16;

	private $feedName;

	public function getIcon() {
		return 'https://s4.bcbits.com/img/bc_favicon.ico';
	}

	public function collectData(){
		switch($this->queriedContext) {
		case 'By tag':
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
			break;
		case 'By band':
			$html = getSimpleHTMLDOMCached($this->getURI(), 86400);
			$this->feedName = $html->find('head meta[name=title]', 0)->content;

			$regex = '/band_id=(\d+)/';
			if(preg_match($regex, $html, $matches) == false)
				returnServerError('Unable to find band ID');
			$band_id = $matches[1];

			$query_data = array(
				'band_id' => $band_id
			);
			$band_data = $this->apiGet('mobile/22/band_details', $query_data);

			$num_albums = min(count($band_data->discography), $this->getInput('limit'));
			for($i = 0; $i < $num_albums; $i++) {
				$album_basic_data = $band_data->discography[$i];

				$query_data = array(
					'band_id' => $band_id,
					'tralbum_type' => 'a',
					'tralbum_id' => $album_basic_data->item_id
				);
				$album_data = $this->apiGet('mobile/22/tralbum_details', $query_data);

				$url = $album_data->bandcamp_url;
				$artist = $album_data->tralbum_artist;
				$title = $album_data->title;
				// e.g. record label is the releaser, but not the artist
				$releaser = $band_data->name !== $artist ? $band_data->name : null;

				$full_title = $artist . ' - ' . $title;
				$full_artist = $artist;
				if (isset($releaser)) {
					$full_title .= ' (' . $releaser . ')';
					$full_artist .= ' (' . $releaser . ')';
				}
				$small_img = $this->getImageUrl($album_data->art_id, self::IMGSIZE_300PX);
				$img = $this->getImageUrl($album_data->art_id, self::IMGSIZE_700PX);

				$item = array(
					'uri' => $url,
					'author' => $full_artist,
					'title' => $full_title
				);
				$item['enclosures'] = array($img);
				$item['timestamp'] = $album_data->release_date;

				$item['categories'] = array();
				foreach ($album_data->tags as $tag) {
					$item['categories'][] = $tag->norm_name;
				}

				// Give articles a unique UID depending on its track list
				// Releases should then show up as new articles when tracks are added
				if ($this->getInput('tracks') === true) {
					$item['uid'] = "bandcamp/$band_id/$album_basic_data->item_id/";
					foreach ($album_data->tracks as $track) {
						$item['uid'] .= $track->track_id;
					}
				}

				$item['content'] = "<img src='$small_img' /><br/>$full_title<br/><ol>";
				foreach ($album_data->tracks as $track) {
					$item['content'] .= "<li>$track->title</li>";
				}
				$item['content'] .= '</ol>';

				if (!empty($album_data->about)) {
					$item['content'] .= '<p>'
						. nl2br($album_data->about)
						. '</p>';
				}

				$this->items[] = $item;
			}

			break;
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

	private function apiGet($endpoint, $query_data) {
		$url = self::URI . 'api/' . $endpoint . '?' . http_build_query($query_data);
		$data = json_decode(getContents($url))
			or returnServerError('API request to "' . $url . '" failed.');
		return $data;
	}

	public function getURI(){
		switch($this->queriedContext) {
		case 'By tag':
			if(!is_null($this->getInput('tag'))) {
				return self::URI
				. 'tag/'
				. urlencode($this->getInput('tag'))
				. '?sort_field=date';
			}
			break;
		case 'By band':
			if(!is_null($this->getInput('band'))) {
				return 'https://' . $this->getInput('band') . '.bandcamp.com';
			}
			break;
		}

		return parent::getURI();
	}

	public function getName(){
		switch($this->queriedContext) {
		case 'By tag':
			if(!is_null($this->getInput('tag'))) {
				return $this->getInput('tag') . ' - Bandcamp Tag';
			}
			break;
		case 'By band':
			if(isset($this->feedName)) {
				return $this->feedName . ' - Bandcamp Band';
			} elseif(!is_null($this->getInput('band'))) {
				return $this->getInput('band') . ' - Bandcamp Band';
			}
			break;
		}

		return parent::getName();
	}

	public function detectParameters($url) {
		$params = array();

		// By band
		$regex = '/^(https?:\/\/)?([^\/.&?\n]+?)\.bandcamp\.com/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['band'] = urldecode($matches[2]);
			$params['tracks'] = 'on';
			return $params;
		}

		// By tag
		$regex = '/^(https?:\/\/)?bandcamp\.com\/tag\/([^\/.&?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['tag'] = urldecode($matches[2]);
			return $params;
		}

		return null;
	}
}
