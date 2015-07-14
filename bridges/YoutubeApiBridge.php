<?php
/**
* RssBridgeYouTubeApi
* Returns the newest videos
*
* @name YouTube Data API Bridge
* @homepage https://www.youtube.com/
* @description Returns the newest videos by channel name or playlist id using the YouTube Data API
* @maintainer logmanoriginal
* @update 2015-07-13
* @use1(k="YouTube Data API key", u="Channel name", i="Number of feed items")
* @use3(k="YouTube Data API key", p="Playlist id")
*/
class YoutubeApiBridge extends BridgeAbstract{
    
	private $request;
    
	public function collectData(array $param){

		$count = 0; // Number of feed items to return
		
		$api = new StdClass(); // Cache for the YouTube Data API
		
		// Load the API key
		if (isset($param['k']) && $param['k'] != "") {
			$api->key = $param['k'];
		} else {
			$this->returnError('You must specify a valid API key (?k=...)', 400);
		}
		
		// Load number of feed items (count)
		if (isset($param['u']) && isset($param['i']) && is_numeric($param['i'])) {
			$count = (int)$param['i'];
		} 
		else if (isset($param['p'])) { 
			// not required
		} else {
			$this->returnError('You must specify the number of items to return (?i=...)', 400);
		}
		
		// Retrieve information by channel name
		if (isset($param['u'])) {
			$this->request = $param['u'];
			
			// We have to acquire the channel id first.
			// For some reason an error from the API results in a false from file_get_contents, so we've to handle that.
			$api->channels = file_get_contents('https://www.googleapis.com/youtube/v3/channels?part=contentDetails&forUsername=' . urlencode($this->request) . '&key=' . $api->key);
			if($api->channels == false) { 
				$this->returnError('Request failed! Check channel name and API key!', 400); 
			}
			$channels = json_decode($api->channels);
			
			// Calculate number of requests (max. 50 items per request possible)
			$req_count = (int)($count / 50);
			
			if($count % 50 <> 0) {
				$req_count++;
			}
			
			// Each page is identified by a page token, the first page has none.
			$pageToken = '';
			
			// Go through all pages
			for($i = 1; $i <= $req_count; $i++){
				$api->playlistItems = file_get_contents('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet%2CcontentDetails%2Cstatus&maxResults=50&playlistId=' . $channels->items[0]->contentDetails->relatedPlaylists->uploads . '&pageToken=' . $pageToken . '&key=' . $api->key);
				$playlistItems = json_decode($api->playlistItems);
				
				// Get the next token
				$pageToken = $playlistItems->nextPageToken;

				foreach($playlistItems->items as $element) {
					$item = new \Item();
					$item->id = $element->contentDetails->videoId;
					$item->uri = 'https://www.youtube.com/watch?v='.$item->id;
					$item->thumbnailUri = $element->snippet->thumbnails->{'default'}->url;
					$item->title = htmlspecialchars($element->snippet->title);
					$item->timestamp = strtotime($element->snippet->publishedAt);
					$item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br><a href="' . $item->uri . '">' . $item->title . '</a>';
					$this->items[] = $item;
					
					// Stop once the number of requested items is reached
					if(count($this->items) >= $count) {
						break;
					}
				}
			}
		}
		
		// Retrieve information by playlist
		else if (isset($param['p'])) {
			$this->request = $param['p'];
			
			// Reading playlist information is similar to how it works on a channel. We don't need a channel id though.
			// For a playlist we always return all items. YouTube has a limit of 200 items per playlist, so the maximum is 4 calls to the API.
			
			$pageToken = '';
			
			do {
				$api->playlistItems = file_get_contents('https://www.googleapis.com/youtube/v3/playlistItems?part=snippet%2CcontentDetails%2Cstatus&maxResults=50&playlistId=' . $this->request . '&pageToken=' . $pageToken . '&key=' . $api->key);
				$playlistItems = json_decode($api->playlistItems);

				foreach($playlistItems->items as $element) {
					$item = new \Item();
					$item->id = $element->contentDetails->videoId;
					$item->uri = 'https://www.youtube.com/watch?v='.$item->id;
					$item->thumbnailUri = $element->snippet->thumbnails->{'default'}->url;
					$item->title = htmlspecialchars($element->snippet->title);
					$item->timestamp = strtotime($element->snippet->publishedAt);
					$item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br><a href="' . $item->uri . '">' . $item->title . '</a>';
					$this->items[] = $item;
				}
				
				if (isset($playlistItems->nextPageToken)) {
					$pageToken = $playlistItems->nextPageToken;
				} else { 
					$pageToken = ''; 
				}
			} while ($pageToken != '');
		}
	}

	public function getName(){
		return (!empty($this->request) ? $this->request .' - ' : '') . 'YouTube API Bridge';
	}

	public function getURI(){
		return 'https://www.youtube.com/';
	}

	public function getCacheDuration(){
		return 10800; // 3 hours
	}
}
?>