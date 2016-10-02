<?php
define('TWITCH_LIMIT', 10); // The default limit

class TwitchApiBridge extends BridgeAbstract{

	const MAINTAINER = "logmanoriginal";
	const NAME = "Twitch API Bridge";
	const URI = "http://www.twitch.tv";
	const CACHE_TIMEOUT = 10800; // 3h
	const DESCRIPTION = "Returns the newest broadcasts or highlights by channel name using the Twitch API (v3)";

    const PARAMETERS = array(
        'Show Channel Videos'=>array(
            'channel'=>array(
                'name'=>'Channel',
                'required'=>true
            ),
            'broadcasts'=>array(
                'name'=>'Show Broadcasts rather than Highlights',
                'type'=>'checkbox'
            ),
            'limit'=>array(
                'name'=>'Limit',
                'type'=>'number'
            )
        )
    );

	public function collectData(){

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

		$limit = $this->getInput('limit');
		if(!$limit){
			$limit = TWITCH_LIMIT;
		}

		// The Twitch API allows a limit between 1 .. 100. Therefore any value below must be set to 1, any greater must result in multiple requests.
        $requests=1;
		if($limit < 1) { $limit = 1; }
		if($limit > 100) {
			$requests = (int)($limit / 100);
			if($limit % 100 != 0) { $requests++; }
		}

		if($this->getInput('broadcasts')){
			$broadcasts='true';
		}else{
			$broadcasts='false';
		}


		// Build the initial request, see also: https://github.com/justintv/Twitch-API/blob/master/v3_resources/videos.md#get-channelschannelvideos
		$request = '';

		if($requests == 1) {
			$request = 'https://api.twitch.tv/kraken/channels/' . $this->getInput('channel') . '/videos?limit=' . $limit . '&broadcasts=' . $broadcasts;
		} else {
			$request = 'https://api.twitch.tv/kraken/channels/' . $this->getInput('channel') . '/videos?limit=100&broadcasts=' . $broadcasts;
		}

		/* Finally we're ready to request data from the API. Each response provides information for the next request. */
		for($i = 0; $i < $requests; $i++) {
			$response = getSimpleHTMLDOM($request, false, $context);

			if($response == false) {
				returnServerError('Request failed! Check if the channel name is valid!');
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
		return $this->getInput('channel') . ' - Twitch API Bridge';
	}
}
?>
