<?php
/**
* RssBridgeYoutube 
* Returns the newest videos
* WARNING: to parse big playlists (over ~90 videos), you need to edit simple_html_dom.php:
* change: define('MAX_FILE_SIZE', 600000);
* into:   define('MAX_FILE_SIZE', 900000);  (or more)
*/
class YoutubeBridge extends BridgeAbstract {


	public function loadMetadatas() {

		$this->name = "Youtube Bridge";

		$this->homepage = "https://youtube.com";
		$this->description = "Returns the 10 newest videos by username/channel/playlist or search";
		$this->maintainer = "mitsukarenai";

		$this->parameters["By username"] =
		'[
			{
				"type" : "text",
				"identifier" : "u",
				"name" : "username",
				"exampleValue" : "test",
				"required" : "required"
			}
		]';

		$this->parameters['By channel id'] =
		'[
			{
				"type" : "text",
				"identifier" : "c",
				"name" : "channel id",
				"exampleValue" : "test",
				"required" : "required"
			}
		]';

		$this->parameters['By playlist Id'] =
		'[
			{
				"type" : "number",
				"identifier" : "c",
				"name" : "playlist id",
				"exampleValue" : "15"
			}
		]';

		$this->parameters["Search result"] =
		'[
			{
				"type" : "text",
				"identifier" : "s",
				"name" : "search keyword",
				"exampleValue" : "test"

			},
			{
				"type" : "number",
				"identifier" : "pa",
				"name" : "page",
				"exampleValue" : "1"

			}
		]';
	}

	public function collectData(array $param){

		function getPublishDate($id) {
			$html2 = file_get_html("https://www.youtube.com/watch?v=$id");
			$timestamp = strtotime($html2->find('meta[itemprop=datePublished]', 0)->getAttribute('content') );
			return $timestamp;
		}  


        	$html = '';
		$limit = 10;
		$count = 0;

		if (isset($param['u'])) {   /* user timeline mode */
			$this->request = $param['u'];
			$html = file_get_html('https://www.youtube.com/user/'.urlencode($this->request).'/videos') or $this->returnError('Could not request Youtube.', 404);

			foreach($html->find('li.channels-content-item') as $element) {
				if($count < $limit) {
					$item = new \Item();
						$videoquery = parse_url($element->find('a',0)->href, PHP_URL_QUERY); parse_str($videoquery, $videoquery);
					$item->id = $videoquery['v'];
					$item->uri = 'https://www.youtube.com/watch?v='.$item->id;
					$item->thumbnailUri = 'https:'.$element->find('img',0)->src;
					$item->title = trim($element->find('h3',0)->plaintext);
					$item->timestamp = getPublishDate($item->id);
					$item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br><a href="' . $item->uri . '">' . $item->title . '</a>';
					$this->items[] = $item;
					$count++;
				}
			}
		}

		else if (isset($param['c'])) {   /* channel timeline mode */
			$this->request = $param['c'];
			$html = file_get_html('https://www.youtube.com/channel/'.urlencode($this->request).'/videos') or $this->returnError('Could not request Youtube.', 404);

			foreach($html->find('li.channels-content-item') as $element) {
				if($count < $limit) {
					$item = new \Item();
						$videoquery = parse_url($element->find('a',0)->href, PHP_URL_QUERY); parse_str($videoquery, $videoquery);
					$item->id = $videoquery['v'];
					$item->uri = 'https://www.youtube.com/watch?v='.$item->id;
					$item->thumbnailUri = 'https:'.$element->find('img',0)->src;
					$item->title = trim($element->find('h3',0)->plaintext);
					$item->timestamp = getPublishDate($item->id);
					$item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br><a href="' . $item->uri . '">' . $item->title . '</a>';
					$this->items[] = $item;
					$count++;
				}
			}
		}

		else if (isset($param['p'])) {   /* playlist mode */
			$this->request = $param['p'];
			$html = file_get_html('https://www.youtube.com/playlist?list='.urlencode($this->request).'') or $this->returnError('Could not request Youtube.', 404);

			foreach($html->find('tr.pl-video') as $element) {
				if($count < $limit) {
					$item = new \Item();
					$item->uri = 'https://www.youtube.com'.$element->find('.pl-video-title a',0)->href;
					$item->thumbnailUri = $element->find('img',0)->getAttribute('data-thumb');
					$item->title = trim($element->find('.pl-video-title a',0)->plaintext);
					$item->id = str_replace('/watch?v=', '', $element->find('a',0)->href);
					$item->timestamp = getPublishDate($item->id);
					$item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br><a href="' . $item->uri . '">' . $item->title . '</a>';
					$this->items[] = $item;
					$count++;
				}
				$this->request = 'Playlist '.trim(str_replace(' - YouTube', '', $html->find('title', 0)->plaintext)).', by '.$html->find('h1', 0)->plaintext;
			}
		}

			else if (isset($param['s'])) {   /* search mode */
				$this->request = $param['s']; $page = 1; if (isset($param['pa'])) $page = (int)preg_replace("/[^0-9]/",'', $param['pa']); 
				$html = file_get_html('https://www.youtube.com/results?search_query='.urlencode($this->request).'&page='.$page.'&filters=video&search_sort=video_date_uploaded') or $this->returnError('Could not request Youtube.', 404);

				foreach($html->find('div.yt-lockup') as $element) {
					$item = new \Item();
					$item->uri = 'https://www.youtube.com'.$element->find('a',0)->href;
					$checkthumb = $element->find('img', 0)->getAttribute('data-thumb');
					if($checkthumb !== FALSE)
						$item->thumbnailUri = $checkthumb;
					else
						$item->thumbnailUri = ''.$element->find('img',0)->src;
					$item->title = trim($element->find('h3',0)->plaintext);
					$item->id = str_replace('/watch?v=', '', $element->find('a',0)->href);
					//$item->timestamp = getPublishDate($item->id);  /* bogus: better not use it  */
					$item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a><br><a href="' . $item->uri . '">' . $item->title . '</a>';
					$this->items[] = $item;
				}
				$this->request = 'Search: '.str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
			}
			else
				$this->returnError('You must either specify a Youtube username (?u=...) or a channel id (?c=...) or a playlist id (?p=...) or search (?s=...)', 400);
		}

	public function getName(){
		return (!empty($this->request) ? $this->request .' - ' : '') .'Youtube Bridge';
	}

	public function getURI(){
		return 'https://www.youtube.com/';
	}

	public function getCacheDuration(){
		return 10800; // 3 hours
	}
}
