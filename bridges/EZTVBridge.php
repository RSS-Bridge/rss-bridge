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
				'exampleValue' => 'showID1,showID2,…',
				'required' => true
			)
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
				$this->items[] = $this->getItemFromTorrent($torrent);
			}
		}

		// Sort all torrents in array by date
		usort($this->items, array('EZTVBridge', 'compareDate'));
	}
}
