<?php
/**
* RssBridgeYoutube
* Returns the newest videos
* WARNING: to parse big playlists (over ~90 videos), you need to edit simple_html_dom.php:
* change: define('MAX_FILE_SIZE', 600000);
* into:   define('MAX_FILE_SIZE', 900000);  (or more)
*/
class YoutubeBridge extends BridgeAbstract {

	const NAME = 'YouTube Bridge';
	const URI = 'https://www.youtube.com/';
	const CACHE_TIMEOUT = 10800; // 3h
	const DESCRIPTION = 'Returns the 10 newest videos by username/channel/playlist or search';
	const MAINTAINER = 'mitsukarenai';

    const PARAMETERS = array(
        'By username' => array(
            'u'=>array(
                'name'=>'username',
                'exampleValue'=>'test',
                'required'=>true
            )
        ),
        'By channel id' => array(
            'c'=>array(
                'name'=>'channel id',
                'exampleValue'=>"15",
                'required'=>true
            )
        ),
        'By playlist Id' => array(
            'p'=>array(
                'name'=>'playlist id',
                'exampleValue'=>"15"
            )
        ),
        'Search result' => array(
            's'=>array(
                'name'=>'search keyword',
                'exampleValue'=>'test'
            ),
            'pa'=>array(
                'name'=>'page',
                'type'=>'number',
                'exampleValue'=>1
            )
        )
    );

	private function ytBridgeQueryVideoInfo($vid, &$author, &$desc, &$time) {
		$html = getSimpleHTMLDOM(self::URI."watch?v=$vid");
		$author = $html->innertext;
		$author = substr($author, strpos($author, '"author=') + 8);
		$author = substr($author, 0, strpos($author, '\u0026'));
		$desc = $html->find('div#watch-description-text', 0)->innertext;
		$time = strtotime($html->find('meta[itemprop=datePublished]', 0)->getAttribute('content'));
	}

	private function ytBridgeAddItem($vid, $title, $author, $desc, $time) {
		$item = array();
		$item['id'] = $vid;
		$item['title'] = $title;
		$item['author'] = $author;
		$item['timestamp'] = $time;
		$item['uri'] = self::URI.'watch?v='.$vid;
		$thumbnailUri = str_replace('/www.', '/img.', self::URI).'vi/'.$vid.'/0.jpg';
		$item['content'] = '<a href="'.$item['uri'].'"><img src="'.$thumbnailUri.'" /></a><br />'.$desc;
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

	public function collectData(){

		$xml = '';
		$html = '';
		$url_feed = '';
		$url_listing = '';

		if ($this->getInput('u')) { /* User and Channel modes */
			$this->request = $this->getInput('u');
			$url_feed = self::URI.'feeds/videos.xml?user='.urlencode($this->request);
			$url_listing = self::URI.'user/'.urlencode($this->request).'/videos';
		} else if ($this->getInput('c')) {
			$this->request = $this->getInput('c');
			$url_feed = self::URI.'feeds/videos.xml?channel_id='.urlencode($this->request);
			$url_listing = self::URI.'channel/'.urlencode($this->request).'/videos';
		}
		if (!empty($url_feed) && !empty($url_listing)) {
			if ($xml = getSimpleHTMLDOM($url_feed)) {
				$this->ytBridgeParseXmlFeed($xml);
			} else if ($html = getSimpleHTMLDOM($url_listing)) {
				$this->ytBridgeParseHtmlListing($html, 'li.channels-content-item', 'h3');
			} else returnServerError("Could not request YouTube. Tried:\n - $url_feed\n - $url_listing");
		}

		else if ($this->getInput('p')) { /* playlist mode */
			$this->request = $this->getInput('p');
			$url_listing = self::URI.'playlist?list='.urlencode($this->request);
			$html = getSimpleHTMLDOM($url_listing) or returnServerError("Could not request YouTube. Tried:\n - $url_listing");
			$this->ytBridgeParseHtmlListing($html, 'tr.pl-video', '.pl-video-title a');
			$this->request = 'Playlist: '.str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
		}

		else if ($this->getInput('s')) { /* search mode */
			$this->request = $this->getInput('s'); $page = 1; if ($this->getInput('pa')) $page = (int)preg_replace("/[^0-9]/",'', $this->getInput('pa'));
			$url_listing = self::URI.'results?search_query='.urlencode($this->request).'&page='.$page.'&filters=video&search_sort=video_date_uploaded';
			$html = getSimpleHTMLDOM($url_listing) or returnServerError("Could not request YouTube. Tried:\n - $url_listing");
			$this->ytBridgeParseHtmlListing($html, 'div.yt-lockup', 'h3');
			$this->request = 'Search: '.str_replace(' - YouTube', '', $html->find('title', 0)->plaintext);
		}

		else { /* no valid mode */
			returnClientError("You must either specify either:\n - YouTube username (?u=...)\n - Channel id (?c=...)\n - Playlist id (?p=...)\n - Search (?s=...)");
		}
	}

	public function getName(){
		return (!empty($this->request) ? $this->request .' - ' : '') .'YouTube Bridge';
	}
}
