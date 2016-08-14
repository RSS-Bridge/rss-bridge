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

		$this->name = 'YouTube Bridge';
		$this->uri = 'https://www.youtube.com/';
		$this->description = 'Returns the 10 newest videos by username/channel/playlist or search';
		$this->maintainer = 'mitsukarenai';
		$this->update = '2016-08-15';

		$this->parameters['By username'] =
		'[
			{
				"type" : "text",
				"identifier" : "u",
				"name" : "username",
				"exampleValue" : "test",
				"required" : true
			}
		]';

		$this->parameters['By channel id'] =
		'[
			{
				"type" : "text",
				"identifier" : "c",
				"name" : "channel id",
				"exampleValue" : "15",
				"required" : true
			}
		]';

		$this->parameters['By playlist Id'] =
		'[
			{
				"type" : "text",
				"identifier" : "p",
				"name" : "playlist id",
				"exampleValue" : "15"
			}
		]';

		$this->parameters['Search result'] =
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

	private function ytBridgeQueryVideoInfo($vid, &$author, &$desc, &$time) {
		$html = $this->file_get_html($this->uri."watch?v=$vid");
		$author = $html->innertext;
		$author = substr($author, strpos($author, '"author=') + 8);
		$author = substr($author, 0, strpos($author, '\u0026'));
		$desc = $html->find('div#watch-description-text', 0)->innertext;
		$time = strtotime($html->find('meta[itemprop=datePublished]', 0)->getAttribute('content'));
	}

	private function ytBridgeAddItem($vid, $title, $author, $desc, $time) {
		$item = new \Item();
		$item->id = $vid;
		$item->title = $title;
		$item->author = $author;
		$item->timestamp = $time;
		$item->uri = $this->uri.'watch?v='.$vid;
		$thumbnailUri = str_replace('/www.', '/img.', $this->uri).'vi/'.$vid.'/0.jpg';
		$item->content = '<a href="'.$item->uri.'"><img src="'.$thumbnailUri.'" /></a><br />'.$desc;
		$this->items[] = $item;
	}

	private function ytBridgeParseXmlFeed($xml) {
		foreach ($xml->find('entry') as $element) {
			$title = $this->ytBridgeFixTitle($element->find('title',0)->plaintext);
			$author = $element->find('name', 0)->plaintext;
			$desc = $element->find('media:description', 0)->innertext;
			$vid = str_replace('yt:video:', '', $element->find('id', 0)->plaintext);
			$time = strtotime($element->find('published', 0)->plaintext);
			$this->ytBridgeAddItem($vid, $title, $author, $desc, $time);
		}
		$this->request = $this->ytBridgeFixTitle($xml->find('feed > title', 0)->plaintext);
	}

	private function ytBridgeParseHtmlListing($html, $element_selector, $title_selector) {
		$limit = 10; $count = 0;
		foreach ($html->find($element_selector) as $element) {
			if ($count < $limit) {
				$author = ''; $desc = ''; $time = 0;
				$vid = str_replace('/watch?v=', '', $element->find('a', 0)->href);
				$title = $this->ytBridgeFixTitle($element->find($title_selector, 0)->plaintext);
				if ($title != '[Private Video]') {
					$this->ytBridgeQueryVideoInfo($vid, $author, $desc, $time);
					$this->ytBridgeAddItem($vid, $title, $author, $desc, $time);
					$count++;
				}
			}
		}
	}

	private function ytBridgeFixTitle($title) {
		// convert both &#1234; and &quot; to UTF-8
		return html_entity_decode($title,ENT_QUOTES,'UTF-8');
	}

	public function collectData(array $param) {

		$xml = '';
		$html = '';
		$url_feed = '';
		$url_listing = '';

		if (isset($param['u'])) { /* User and Channel modes */
			$this->request = $param['u'];
			$url_feed = $this->uri.'feeds/videos.xml?user='.urlencode($this->request);
			$url_listing = $this->uri.'user/'.urlencode($this->request).'/videos';
		} else if (isset($param['c'])) {
			$this->request = $param['c'];
			$url_feed = $this->uri.'feeds/videos.xml?channel_id='.urlencode($this->request);
			$url_listing = $this->uri.'channel/'.urlencode($this->request).'/videos';
		}
		if (!empty($url_feed) && !empty($url_listing)) {
			if ($xml = $this->file_get_html($url_feed)) {
				$this->ytBridgeParseXmlFeed($xml);
			} else if ($html = $this->file_get_html($url_listing)) {
				$this->ytBridgeParseHtmlListing($html, 'li.channels-content-item', 'h3');
			} else $this->returnError("Could not request YouTube. Tried:\n - $url_feed\n - $url_listing", 500);
		}

		else if (isset($param['p'])) { /* playlist mode */
			$this->request = $param['p'];
			$url_listing = $this->uri.'playlist?list='.urlencode($this->request);
			$html = $this->file_get_html($url_listing) or $this->returnError("Could not request YouTube. Tried:\n - $url_listing", 500);
			$this->ytBridgeParseHtmlListing($html, 'tr.pl-video', '.pl-video-title a');
			$this->request = 'Playlist: '.str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
		}

		else if (isset($param['s'])) { /* search mode */
			$this->request = $param['s']; $page = 1; if (isset($param['pa'])) $page = (int)preg_replace("/[^0-9]/",'', $param['pa']); 
			$url_listing = $this->uri.'results?search_query='.urlencode($this->request).'&page='.$page.'&filters=video&search_sort=video_date_uploaded';
			$html = $this->file_get_html($url_listing) or $this->returnError("Could not request YouTube. Tried:\n - $url_listing", 500);
			$this->ytBridgeParseHtmlListing($html, 'div.yt-lockup', 'h3');
			$this->request = 'Search: '.str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
		}

		else { /* no valid mode */
			$this->returnError("You must either specify either:\n - YouTube username (?u=...)\n - Channel id (?c=...)\n - Playlist id (?p=...)\n - Search (?s=...)", 400);
		}
	}

	public function getName(){
		return (!empty($this->request) ? $this->request .' - ' : '') .'YouTube Bridge';
	}

	public function getCacheDuration(){
		return 10800; // 3 hours
	}
}
