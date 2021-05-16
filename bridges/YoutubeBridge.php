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
	const MAINTAINER = 'em92';

	const PARAMETERS = array(
		'By username' => array(
			'u' => array(
				'name' => 'username',
				'exampleValue' => 'test',
				'required' => true
			)
		),
		'By channel id' => array(
			'c' => array(
				'name' => 'channel id',
				'exampleValue' => '15',
				'required' => true
			)
		),
		'By playlist Id' => array(
			'p' => array(
				'name' => 'playlist id',
				'exampleValue' => '15'
			)
		),
		'Search result' => array(
			's' => array(
				'name' => 'search keyword',
				'exampleValue' => 'test'
			),
			'pa' => array(
				'name' => 'page',
				'type' => 'number',
				'exampleValue' => 1
			)
		),
		'global' => array(
			'duration_min' => array(
				'name' => 'min. duration (minutes)',
				'type' => 'number',
				'title' => 'Minimum duration for the video in minutes',
				'exampleValue' => 5
			),
			'duration_max' => array(
				'name' => 'max. duration (minutes)',
				'type' => 'number',
				'title' => 'Maximum duration for the video in minutes',
				'exampleValue' => 10
			)
		)
	);

	private $feedName = '';

	private function ytBridgeQueryVideoInfo($vid, &$author, &$desc, &$time){
		$html = $this->ytGetSimpleHTMLDOM(self::URI . "watch?v=$vid", true);

		// Skip unavailable videos
		if(strpos($html->innertext, 'IS_UNAVAILABLE_PAGE') !== false) {
			return;
		}

		$elAuthor = $html->find('span[itemprop=author] > link[itemprop=name]', 0);
		if (!is_null($elAuthor)) {
			$author = $elAuthor->getAttribute('content');
		}

		$elDatePublished = $html->find('meta[itemprop=datePublished]', 0);
		if(!is_null($elDatePublished))
			$time = strtotime($elDatePublished->getAttribute('content'));

		$scriptRegex = '/var ytInitialData = (.*);<\/script>/';
		preg_match($scriptRegex, $html, $matches) or returnServerError('Could not find ytInitialData');
		$jsonData = json_decode($matches[1]);
		$jsonData = $jsonData->contents->twoColumnWatchNextResults->results->results->contents;

		$videoSecondaryInfo = null;
		foreach($jsonData as $item) {
			if (isset($item->videoSecondaryInfoRenderer)) {
				$videoSecondaryInfo = $item->videoSecondaryInfoRenderer;
				break;
			}
		}
		if (!$videoSecondaryInfo) {
			returnServerError('Could not find videoSecondaryInfoRenderer');
		}
		$desc = nl2br($videoSecondaryInfo->description->runs[0]->text);
	}

	private function ytBridgeAddItem($vid, $title, $author, $desc, $time){
		$item = array();
		$item['id'] = $vid;
		$item['title'] = $title;
		$item['author'] = $author;
		$item['timestamp'] = $time;
		$item['uri'] = self::URI . 'watch?v=' . $vid;
		$thumbnailUri = str_replace('/www.', '/img.', self::URI) . 'vi/' . $vid . '/0.jpg';
		$item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a><br />' . $desc;
		$this->items[] = $item;
	}

	private function ytBridgeParseXmlFeed($xml) {
		foreach($xml->find('entry') as $element) {
			$title = $this->ytBridgeFixTitle($element->find('title', 0)->plaintext);
			$author = $element->find('name', 0)->plaintext;
			$desc = $element->find('media:description', 0)->innertext;

			// Make sure the description is easy on the eye :)
			$desc = htmlspecialchars($desc);
			$desc = nl2br($desc);
			$desc = preg_replace('/(http[s]{0,1}\:\/\/[a-zA-Z0-9.\/\?\&=\-_]{4,})/ims',
				'<a href="$1" target="_blank">$1</a> ',
				$desc);

			$vid = str_replace('yt:video:', '', $element->find('id', 0)->plaintext);
			$time = strtotime($element->find('published', 0)->plaintext);
			if(strpos($vid, 'googleads') === false)
				$this->ytBridgeAddItem($vid, $title, $author, $desc, $time);
		}
		$this->feedName = $this->ytBridgeFixTitle($xml->find('feed > title', 0)->plaintext);  // feedName will be used by getName()
	}

	private function ytBridgeParseHtmlListing($html, $element_selector, $title_selector, $add_parsed_items = true) {
		$count = 0;

		$duration_min = $this->getInput('duration_min') ?: -1;
		$duration_min = $duration_min * 60;

		$duration_max = $this->getInput('duration_max') ?: INF;
		$duration_max = $duration_max * 60;

		if($duration_max < $duration_min) {
			returnClientError('Max duration must be greater than min duration!');
		}

		foreach($html->find($element_selector) as $element) {
			$author = '';
			$desc = '';
			$time = 0;
			$vid = str_replace('/watch?v=', '', $element->find('a', 0)->href);
			$vid = substr($vid, 0, strpos($vid, '&') ?: strlen($vid));
			$title = trim($this->ytBridgeFixTitle($element->find($title_selector, 0)->plaintext));

			if (strpos($vid, 'googleads') !== false
				|| $title == '[Private video]'
				|| $title == '[Deleted video]'
			) {
				continue;
			}

			// The duration comes in one of the formats:
			// hh:mm:ss / mm:ss / m:ss
			// 01:03:30 / 15:06 / 1:24
			$durationText = trim($element->find('div.timestamp span', 0)->plaintext);
			$durationText = preg_replace('/([\d]{1,2})\:([\d]{2})/', '00:$1:$2', $durationText);

			sscanf($durationText, '%d:%d:%d', $hours, $minutes, $seconds);
			$duration = $hours * 3600 + $minutes * 60 + $seconds;

			if($duration < $duration_min || $duration > $duration_max) {
				continue;
			}

			if ($add_parsed_items) {
				$this->ytBridgeQueryVideoInfo($vid, $author, $desc, $time);
				$this->ytBridgeAddItem($vid, $title, $author, $desc, $time);
			}
			$count++;
		}
		return $count;
	}

	private function ytBridgeFixTitle($title) {
		// convert both &#1234; and &quot; to UTF-8
		return html_entity_decode($title, ENT_QUOTES, 'UTF-8');
	}

	private function ytGetSimpleHTMLDOM($url, $cached = false){
		$header = array(
			'Accept-Language: en-US'
		);
		$opts = array();
		$lowercase = true;
		$forceTagsClosed = true;
		$target_charset = DEFAULT_TARGET_CHARSET;
		$stripRN = false;
		$defaultBRText = DEFAULT_BR_TEXT;
		$defaultSpanText = DEFAULT_SPAN_TEXT;
		if ($cached) {
			return getSimpleHTMLDOMCached($url,
				86400,
				$header,
				$opts,
				$lowercase,
				$forceTagsClosed,
				$target_charset,
				$stripRN,
				$defaultBRText,
				$defaultSpanText);
		}
		return getSimpleHTMLDOM($url,
			$header,
			$opts,
			$lowercase,
			$forceTagsClosed,
			$target_charset,
			$stripRN,
			$defaultBRText,
			$defaultSpanText);
	}

	public function collectData(){

		$xml = '';
		$html = '';
		$url_feed = '';
		$url_listing = '';

		if($this->getInput('u')) { /* User and Channel modes */
			$this->request = $this->getInput('u');
			$url_feed = self::URI . 'feeds/videos.xml?user=' . urlencode($this->request);
			$url_listing = self::URI . 'user/' . urlencode($this->request) . '/videos';
		} elseif($this->getInput('c')) {
			$this->request = $this->getInput('c');
			$url_feed = self::URI . 'feeds/videos.xml?channel_id=' . urlencode($this->request);
			$url_listing = self::URI . 'channel/' . urlencode($this->request) . '/videos';
		}

		if(!empty($url_feed) && !empty($url_listing)) {
			if(!$this->skipFeeds() && $xml = $this->ytGetSimpleHTMLDOM($url_feed)) {
				$this->ytBridgeParseXmlFeed($xml);
			} elseif($html = $this->ytGetSimpleHTMLDOM($url_listing)) {
				$this->ytBridgeParseHtmlListing($html, 'li.channels-content-item', 'h3');
			} else {
				returnServerError("Could not request YouTube. Tried:\n - $url_feed\n - $url_listing");
			}
		} elseif($this->getInput('p')) { /* playlist mode */
			// TODO: this mode makes a lot of excess video query requests.
			// To make less requests, we need to cache following dictionary "videoId -> datePublished, duration"
			// This cache will be used to find out, which videos to fetch
			// to make feed of 15 items or more, if there a lot of videos published on that date.
			$this->request = $this->getInput('p');
			$url_feed = self::URI . 'feeds/videos.xml?playlist_id=' . urlencode($this->request);
			$url_listing = self::URI . 'playlist?list=' . urlencode($this->request);
			$html = $this->ytGetSimpleHTMLDOM($url_listing)
				or returnServerError("Could not request YouTube. Tried:\n - $url_listing");
			$scriptRegex = '/var ytInitialData = (.*);<\/script>/';
			preg_match($scriptRegex, $html, $matches) or returnServerError('Could not find ytInitialData');
			// TODO: this method returns only first 100 video items
			// if it has more videos, playlistVideoListRenderer will have continuationItemRenderer as last element
			$jsonData = json_decode($matches[1]);
			$jsonData = $jsonData->contents->twoColumnBrowseResultsRenderer->tabs[0];
			$jsonData = $jsonData->tabRenderer->content->sectionListRenderer->contents[0]->itemSectionRenderer;
			$jsonData = $jsonData->contents[0]->playlistVideoListRenderer->contents;
			$item_count = count($jsonData);
			if ($item_count <= 15 && !$this->skipFeeds() && ($xml = $this->ytGetSimpleHTMLDOM($url_feed))) {
				$this->ytBridgeParseXmlFeed($xml);
			} else {
				$this->parseJsonPlaylist($jsonData);
			}
			$this->feedName = 'Playlist: ' . str_replace(' - YouTube', '', $html->find('title', 0)->plaintext); // feedName will be used by getName()
			usort($this->items, function ($item1, $item2) {
				return $item2['timestamp'] - $item1['timestamp'];
			});
		} elseif($this->getInput('s')) { /* search mode */
			$this->request = $this->getInput('s');
			$page = 1;
			if($this->getInput('pa'))
				$page = (int)preg_replace('/[^0-9]/', '', $this->getInput('pa'));

			$url_listing = self::URI
			. 'results?search_query='
			. urlencode($this->request)
			. '&page='
			. $page
			. '&filters=video&search_sort=video_date_uploaded';

			$html = $this->ytGetSimpleHTMLDOM($url_listing)
				or returnServerError("Could not request YouTube. Tried:\n - $url_listing");

			$this->ytBridgeParseHtmlListing($html, 'div.yt-lockup', 'h3 > a');
			$this->feedName = 'Search: ' . str_replace(' - YouTube', '', $html->find('title', 0)->plaintext); // feedName will be used by getName()
		} else { /* no valid mode */
			returnClientError("You must either specify either:\n - YouTube
 username (?u=...)\n - Channel id (?c=...)\n - Playlist id (?p=...)\n - Search (?s=...)");
		}
	}

	private function skipFeeds() {
		return ($this->getInput('duration_min') || $this->getInput('duration_max'));
	}

	public function getURI()
	{
		if (!is_null($this->getInput('p'))) {
			return static::URI . 'playlist?list=' . $this->getInput('p');
		}

		return parent::getURI();
	}

	public function getName(){
	  // Name depends on queriedContext:
		switch($this->queriedContext) {
		case 'By username':
		case 'By channel id':
		case 'By playlist Id':
		case 'Search result':
			return htmlspecialchars_decode($this->feedName) . ' - YouTube'; // We already know it's a bridge, right?
		default:
			return parent::getName();
		}
	}

	private function parseJsonPlaylist($jsonData) {
		$duration_min = $this->getInput('duration_min') ?: -1;
		$duration_min = $duration_min * 60;

		$duration_max = $this->getInput('duration_max') ?: INF;
		$duration_max = $duration_max * 60;

		if($duration_max < $duration_min) {
			returnClientError('Max duration must be greater than min duration!');
		}

		foreach($jsonData as $item) {
			if (!isset($item->playlistVideoRenderer)) {
				continue;
			}
			$vid = $item->playlistVideoRenderer->videoId;
			$title = $item->playlistVideoRenderer->title->runs[0]->text;

			$author = '';
			$desc = '';
			$time = 0;
			$duration = intval($item->playlistVideoRenderer->lengthSeconds);
			if($duration < $duration_min || $duration > $duration_max) {
				continue;
			}

			$this->ytBridgeQueryVideoInfo($vid, $author, $desc, $time);
			$this->ytBridgeAddItem($vid, $title, $author, $desc, $time);
		}
	}
}
