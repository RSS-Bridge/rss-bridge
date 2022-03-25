<?php
class EZTVBridge extends BridgeAbstract {

	const MAINTAINER = 'alexAubin';
	const NAME = 'EZTV';
	const URI = 'https://eztv.re/';
	const DESCRIPTION = 'Returns list of torrents for specific show(s)
on EZTV. Get IMDB IDs from IMDB.';

	const PARAMETERS = array(
		array(
			'ids' => array(
				'name' => 'Show IMDB IDs',
				'exampleValue' => '8740790,1733785',
				'required' => true,
				'title' => 'One or more IMDB show IDs (can be found in the IMDB show URL)'
			),
			'no480' => array(
				'name' => 'No 480p',
				'type' => 'checkbox',
				'title' => 'Activate to exclude 480p torrents'
			),
			'no720' => array(
				'name' => 'No 720p',
				'type' => 'checkbox',
				'title' => 'Activate to exclude 720p torrents'
			),
			'no1080' => array(
				'name' => 'No 1080p',
				'type' => 'checkbox',
				'title' => 'Activate to exclude 1080p torrents'
			),
			'no2160' => array(
				'name' => 'No 2160p',
				'type' => 'checkbox',
				'title' => 'Activate to exclude 2160p torrents'
			),
			'noUnknownRes' => array(
				'name' => 'No Unknown resolution',
				'type' => 'checkbox',
				'title' => 'Activate to exclude unknown resolution torrents'
			),
		)
	);

	// Shamelessly lifted from https://stackoverflow.com/a/2510459
	protected function formatBytes($bytes, $precision = 2) {
		$units = array('B', 'KB', 'MB', 'GB', 'TB');

		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		$bytes /= pow(1024, $pow);

		return round($bytes, $precision) . ' ' . $units[$pow];
	}

	protected function getItemFromTorrent($torrent){
		$item = array();
		$item['uri'] = $torrent->episode_url;
		$item['author'] = $torrent->imdb_id;
		$item['timestamp'] = date('d F Y H:i:s', $torrent->date_released_unix);
		$item['title'] = $torrent->title;
		$item['enclosures'][] = $torrent->torrent_url;

		$thumbnailUri = 'https:' . $torrent->small_screenshot;
		$torrentSize = $this->formatBytes($torrent->size_bytes);

		$item['content'] = $torrent->filename . '<br>File size: '
		. $torrentSize . '<br><a href="' . $torrent->magnet_url
		. '">magnet link</a><br><a href="' . $torrent->torrent_url
		. '">torrent link</a><br><img src="' . $thumbnailUri . '" />';

		return $item;
	}

	private static function compareDate($torrent1, $torrent2) {
		return (strtotime($torrent1['timestamp']) < strtotime($torrent2['timestamp']) ? 1 : -1);
	}

	public function collectData(){
		$showIds = explode(',', $this->getInput('ids'));

		foreach($showIds as $showId) {
			$eztvUri = $this->getURI() . 'api/get-torrents?imdb_id=' . $showId;
			$content = getContents($eztvUri);
			$torrents = json_decode($content)->torrents;
			foreach($torrents as $torrent) {
				$title = $torrent->title;
				$regex480 = '/480p/';
				$regex720 = '/720p/';
				$regex1080 = '/1080p/';
				$regex2160 = '/2160p/';
				$regexUnknown = '/(480p|720p|1080p|2160p)/';
				// Skip unwanted resolution torrents
				if ((preg_match($regex480, $title) === 1 && $this->getInput('no480'))
				|| (preg_match($regex720, $title) === 1 && $this->getInput('no720'))
				|| (preg_match($regex1080, $title) === 1 && $this->getInput('no1080'))
				|| (preg_match($regex2160, $title) === 1 && $this->getInput('no2160'))
				|| (preg_match($regexUnknown, $title) !== 1 && $this->getInput('noUnknownRes'))) {
					continue;
				}

				$this->items[] = $this->getItemFromTorrent($torrent);
			}
		}

		// Sort all torrents in array by date
		usort($this->items, array('EZTVBridge', 'compareDate'));
	}
}
