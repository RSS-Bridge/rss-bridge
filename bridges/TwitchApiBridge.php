<?php
define('TWITCH_LIMIT', 10); // The default limit
define('TWITCH_BROADCASTS', 'false'); // The default flag for broadcasts

class TwitchApiBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "logmanoriginal";
		$this->name = "Twitch API Bridge";
		$this->uri = "http://www.twitch.tv";
		$this->description = "Returns the newest broadcasts or highlights by channel name using the Twitch API (v3)";

        $this->parameters["Get channel without limit"] = array(
          'channel'=>array(
            'name'=>'Channel',
            'required'=>true
          ),
          'broadcasts'=>array(
            'name'=>'Broadcasts',
            'type'=>'list',
            'values'=>array(
              'Show broadcasts'=>'true',
              'Show highlights'=>'false'
            )
          )
        );

        $this->parameters["Get channel with limit"] = array(
          'channel'=>array(
            'name'=>'Channel',
            'required'=>true
          ),
          'limit'=>array(
            'name'=>'Limit',
            'type'=>'number'
          ),
          'broadcasts'=>array(
            'name'=>'Broadcasts',
            'type'=>'list',
            'values'=>array(
              'Show broadcasts'=>'true',
              'Show highlights'=>'false'
            )
          )
        );
	}

	public function collectData(){
        $param=$this->parameters[$this->queriedContext];

		/* In accordance with API description:
		 * "When specifying a version for a request to the Twitch API, set the Accept HTTP header to the API version you prefer."
		 * Now we prefer v3 right now and need to build the context options. */
		$opts = array('https' =>
			array(
				'method'  => 'GET',
				'header'  => 'Accept: application/vnd.twitchtv.v3+json'
			)
		);

		$context = stream_context_create($opts);

		$limit = TWITCH_LIMIT;
		$broadcasts = TWITCH_BROADCASTS;
		$requests = 1;

		if(isset($param['limit']['value'])) {
            $limit = (int)$param['limit']['value'];
		} else {
			$limit = TWITCH_LIMIT;
		}

		// The Twitch API allows a limit between 1 .. 100. Therefore any value below must be set to 1, any greater must result in multiple requests.
		if($limit < 1) { $limit = 1; }
		if($limit > 100) {
			$requests = (int)($limit / 100);
			if($limit % 100 != 0) { $requests++; }
		}

        $broadcasts = $param['broadcasts']['value'];

		// Build the initial request, see also: https://github.com/justintv/Twitch-API/blob/master/v3_resources/videos.md#get-channelschannelvideos
		$request = '';

		if($requests == 1) {
			$request = 'https://api.twitch.tv/kraken/channels/' . $param['channel']['value'] . '/videos?limit=' . $limit . '&broadcasts=' . $broadcasts;
		} else {
			$request = 'https://api.twitch.tv/kraken/channels/' . $param['channel']['value'] . '/videos?limit=100&broadcasts=' . $broadcasts;
		}

		/* Finally we're ready to request data from the API. Each response provides information for the next request. */
		for($i = 0; $i < $requests; $i++) {
			$response = $this->getSimpleHTMLDOM($request, false, $context);

			if($response == false) {
				$this->returnServerError('Request failed! Check if the channel name is valid!');
			}

			$data = json_decode($response);

			foreach($data->videos as $video) {
				$item = array();
				$item['id'] = $video->_id;
				$item['uri'] = $video->url;
				$item['title'] = htmlspecialchars($video->title);
				$item['timestamp'] = strtotime($video->recorded_at);
				$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $video->preview . '" /></a><br><a href="' . $item['uri'] . '">' . $item['title'] . '</a>';
				$this->items[] = $item;

				// Stop once the number of requested items is reached
				if(count($this->items) >= $limit) {
					break;
				}
			}

			// Get next request (if available)
			if(isset($data->_links->next)) {
				$request = $data->_links->next;
			} else {
				break;
			}
		}
	}

	public function getName(){
        $param=$this->parameters[$this->queriedContext];
		return $param['channel'] . ' - Twitch API Bridge';
	}

	public function getCacheDuration(){
		return 10800; // 3 hours
	}
}
?>
