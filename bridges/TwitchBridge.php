<?php
class TwitchBridge extends BridgeAbstract {

	const MAINTAINER = 'Roliga';
	const NAME = 'Twitch Bridge';
	const URI = 'https://twitch.tv/';
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'Twitch channel videos';
	const PARAMETERS = array( array(
		'channel' => array(
			'name' => 'Channel',
			'type' => 'text',
			'required' => true,
			'title' => 'Lowercase channel name as seen in channel URL'
		),
		'type' => array(
			'name' => 'Type',
			'type' => 'list',
			'values' => array(
				'All' => 'all',
				'Archive' => 'archive',
				'Highlights' => 'highlight',
				'Uploads' => 'upload'
			),
			'defaultValue' => 'archive'
		)
	));

	/*
	 * Official instructions for obtaining your own client ID can be found here:
	 * https://dev.twitch.tv/docs/v5/#getting-a-client-id
	 */
	const CLIENT_ID = 'kimne78kx3ncx6brgo4mv6wki5h1ko';

	public function collectData(){
		// get channel user
		$query_data = array(
			'login' => $this->getInput('channel')
		);
		$users = $this->apiGet('users', $query_data)->users;
		if(count($users) === 0)
			returnClientError('User "'
			. $this->getInput('channel')
			. '" could not be found');
		$user = $users[0];

		// get video list
		$query_endpoint = 'channels/' . $user->_id . '/videos';
		$query_data = array(
			'broadcast_type' => $this->getInput('type'),
			'limit' => 10
		);
		$videos = $this->apiGet($query_endpoint, $query_data)->videos;

		foreach($videos as $video) {
			$item = array(
				'uri' => $video->url,
				'title' => $video->title,
				'timestamp' => $video->published_at,
				'author' => $video->channel->display_name,
			);

			// Add categories for tags and played game
			$item['categories'] = array_filter(explode(' ', $video->tag_list));
			if(!empty($video->game))
				$item['categories'][] = $video->game;

			// Add enclosures for thumbnails from a few points in the video
			$item['enclosures'] = array();
			foreach($video->thumbnails->large as $thumbnail)
				$item['enclosures'][] = $thumbnail->url;

			/*
			 * Content format example:
			 *
			 * [Preview Image]
			 *
			 * Some optional video description.
			 *
			 * Duration: 1:23:45
			 * Views: 123
			 *
			 * Played games:
			 * * 00:00:00 Game 1
			 * * 00:12:34 Game 2
			 *
			 */
			$item['content'] = '<p><a href="'
				. $video->url
				. '"><img src="'
				. $video->preview->large
				. '" /></a></p><p>'
				. $video->description_html
				. '</p><p><b>Duration:</b> '
				. $this->formatTimestampTime($video->length)
				. '<br/><b>Views:</b> '
				. $video->views
				. '</p>';

			// Add played games list to content
			$video_id = trim($video->_id, 'v'); // _id gives 'v1234' but API wants '1234'
			$markers = $this->apiGet('videos/' . $video_id . '/markers')->markers;
			$item['content'] .= '<p><b>Played games:</b></b><ul><li><a href="'
				. $video->url
				. '">00:00:00</a> - '
				. $video->game
				. '</li>';
			if(isset($markers->game_changes)) {
				usort($markers->game_changes, function($a, $b) {
					return $a->time - $b->time;
				});
				foreach($markers->game_changes as $game_change) {
					$item['categories'][] = $game_change->label;
					$item['content'] .= '<li><a href="'
						. $video->url
						. '?t='
						. $this->formatQueryTime($game_change->time)
						. '">'
						. $this->formatTimestampTime($game_change->time)
						. '</a> - '
						. $game_change->label
						. '</li>';
				}
			}
			$item['content'] .= '</ul></p>';

			$this->items[] = $item;
		}
	}

	// e.g. 01:53:27
	private function formatTimestampTime($seconds) {
		return sprintf('%02d:%02d:%02d',
			floor($seconds / 3600),
			($seconds / 60) % 60,
			$seconds % 60);
	}

	// e.g. 01h53m27s
	private function formatQueryTime($seconds) {
		return sprintf('%02dh%02dm%02ds',
			floor($seconds / 3600),
			($seconds / 60) % 60,
			$seconds % 60);
	}

	/*
	 * Ideally the new 'helix' API should be used as v5/'kraken' is deprecated.
	 * The new API however still misses many features (markers, played game..) of
	 * the old one, so let's use the old one for as long as it's available.
	 */
	private function apiGet($endpoint, $query_data = array()) {
		$query_data['api_version'] = 5;
		$url = 'https://api.twitch.tv/kraken/'
			. $endpoint
			. '?'
			. http_build_query($query_data);
		$header = array(
			'Client-ID: ' . self::CLIENT_ID
		);

		$data = json_decode(getContents($url, $header))
			or returnServerError('API request to "' . $url . '" failed.');

		return $data;
	}

	public function getName(){
		if(!is_null($this->getInput('channel'))) {
			return $this->getInput('channel') . ' twitch videos';
		}

		return parent::getName();
	}

	public function getURI(){
		if(!is_null($this->getInput('channel'))) {
			return self::URI . $this->getInput('channel');
		}

		return parent::getURI();
	}

	public function detectParameters($url){
		$params = array();

		// Matches e.g. https://www.twitch.tv/someuser/videos?filter=archives
		$regex = '/^(https?:\/\/)?
			(www\.)?
			twitch\.tv\/
			([^\/&?\n]+)
			\/videos\?.*filter=
			(all|archive|highlight|upload)/x';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['channel'] = urldecode($matches[3]);
			$params['type'] = $matches[4];
			return $params;
		}

		return null;
	}
}
