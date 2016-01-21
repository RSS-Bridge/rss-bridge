<?php
define('TWITCH_LIMIT', 10); // The default limit
define('TWITCH_BROADCASTS', 'false'); // The default flag for broadcasts

class TwitchApiBridge extends BridgeAbstract{

	// for use in the getName function!
	private $channel;

	public function loadMetadatas() {

		$this->maintainer = "logmanoriginal";
		$this->name = "Twitch API Bridge";
		$this->uri = "http://www.twitch.tv";
		$this->description = "Returns the newest broadcasts or highlights by channel name using the Twitch API (v3)";
		$this->update = "2016-01-09";

		$this->parameters["Get channel without limit"] =
		'[
			{
				"name" : "Channel",
				"identifier" : "channel"
			},
			{
				"name" : "Broadcasts",
				"identifier" : "broadcasts",
				"type" : "list",
				"values" : [
					{
						"name" : "Show broadcasts",
						"value" : "true"
					},
					{
						"name" : "Show highlights",
						"value" : "false"
					}
				]
			}
		]';
		$this->parameters["Get channel with limit"] =
		'[
			{
				"name" : "Channel",
				"identifier" : "channel"
			},
			{
				"name" : "Limit",
				"identifier" : "limit",
				"type" : "number"
			},
			{
				"name" : "Broadcasts",
				"identifier" : "broadcasts",
				"type" : "list",
				"values" : [
					{
						"name" : "Show broadcasts",
						"value" : "true"
					},
					{
						"name" : "Show highlights",
						"value" : "false"
					}
				]
			}
		]';

	}
    
	public function collectData(array $param){

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
		
		$channel = '';
		$limit = TWITCH_LIMIT;
		$broadcasts = TWITCH_BROADCASTS;
		$requests = 1;
		
		if(isset($param['channel'])) {
			$channel = $param['channel'];
		} else {
			$this->returnError('You must specify a valid channel name! Received: &channel=' . $param['channel'], 400);
		}
		
		$this->channel = $channel;
		
		if(isset($param['limit'])) {
			try {
				$limit = (int)$param['limit'];
			} catch (Exception $e){
				$this->returnError('The limit you specified is not valid! Received: &limit=' . $param['limit'] . ' Expected: &limit=<num> where <num> is any integer number.', 400);
			}
		} else {
			$limit = TWITCH_LIMIT;
		}
		
		// The Twitch API allows a limit between 1 .. 100. Therefore any value below must be set to 1, any greater must result in multiple requests.
		if($limit < 1) { $limit = 1; }
		if($limit > 100) { 
			$requests = (int)($limit / 100); 
			if($limit % 100 != 0) { $requests++; }
		}
		
		if(isset($param['broadcasts']) && ($param['broadcasts'] == 'true' || $param['broadcasts'] == 'false')) {
			$broadcasts = $param['broadcasts'];
		} else {
			$this->returnError('The value for broadcasts you specified is not valid! Received: &broadcasts=' . $param['broadcasts'] . ' Expected: &broadcasts=false or &broadcasts=true', 400);
		}
		
		// Build the initial request, see also: https://github.com/justintv/Twitch-API/blob/master/v3_resources/videos.md#get-channelschannelvideos
		$request = '';
		
		if($requests == 1) {
			$request = 'https://api.twitch.tv/kraken/channels/' . $channel . '/videos?limit=' . $limit . '&broadcasts=' . $broadcasts;
		} else {
			$request = 'https://api.twitch.tv/kraken/channels/' . $channel . '/videos?limit=100&broadcasts=' . $broadcasts;
		}
		
		/* Finally we're ready to request data from the API. Each response provides information for the next request. */
		for($i = 0; $i < $requests; $i++) {
			$response = file_get_contents($request, false, $context);
			
			if($response == false) { 
				$this->returnError('Request failed! Check if the channel name is valid!', 400); 
			}
			
			$data = json_decode($response);
			
			foreach($data->videos as $video) {
				$item = new \Item();
				$item->id = $video->_id;
				$item->uri = $video->url;
				$item->thumbnailUri = $video->preview;
				$item->title = htmlspecialchars($video->title);
				$item->timestamp = strtotime($video->recorded_at);
				$item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br><a href="' . $item->uri . '">' . $item->title . '</a>';
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
		return (!empty($this->channel) ? $this->channel . ' - ' : '') . 'Twitch API Bridge';
	}

	public function getURI(){
		return 'https://www.twitch.tv';
	}

	public function getCacheDuration(){
		return 10800; // 3 hours
	}
}
?>
