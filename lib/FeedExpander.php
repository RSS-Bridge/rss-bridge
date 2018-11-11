<?php

abstract class FeedExpander extends BridgeAbstract {

	private $name;
	private $uri;
	private $feedType;

	public function collectExpandableDatas($url, $maxItems = -1){
		if(empty($url)) {
			returnServerError('There is no $url for this RSS expander');
		}

		Debug::log('Loading from ' . $url);

		/* Notice we do not use cache here on purpose:
		 * we want a fresh view of the RSS stream each time
		 */
		$content = getContents($url)
			or returnServerError('Could not request ' . $url);
		$rssContent = simplexml_load_string(trim($content));

		Debug::log('Detecting feed format/version');
		switch(true) {
		case isset($rssContent->item[0]):
			Debug::log('Detected RSS 1.0 format');
			$this->feedType = 'RSS_1_0';
			break;
		case isset($rssContent->channel[0]):
			Debug::log('Detected RSS 0.9x or 2.0 format');
			$this->feedType = 'RSS_2_0';
			break;
		case isset($rssContent->entry[0]):
			Debug::log('Detected ATOM format');
			$this->feedType = 'ATOM_1_0';
			break;
		default:
			Debug::log('Unknown feed format/version');
			returnServerError('The feed format is unknown!');
			break;
		}

		Debug::log('Calling function "collect_' . $this->feedType . '_data"');
		$this->{'collect_' . $this->feedType . '_data'}($rssContent, $maxItems);
	}

	protected function collect_RSS_1_0_data($rssContent, $maxItems){
		$this->load_RSS_2_0_feed_data($rssContent->channel[0]);
		foreach($rssContent->item as $item) {
			Debug::log('parsing item ' . var_export($item, true));
			$tmp_item = $this->parseItem($item);
			if (!empty($tmp_item)) {
				$this->items[] = $tmp_item;
			}
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	protected function collect_RSS_2_0_data($rssContent, $maxItems){
		$rssContent = $rssContent->channel[0];
		Debug::log('RSS content is ===========\n'
		. var_export($rssContent, true)
		. '===========');

		$this->load_RSS_2_0_feed_data($rssContent);
		foreach($rssContent->item as $item) {
			Debug::log('parsing item ' . var_export($item, true));
			$tmp_item = $this->parseItem($item);
			if (!empty($tmp_item)) {
				$this->items[] = $tmp_item;
			}
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	protected function collect_ATOM_1_0_data($content, $maxItems){
		$this->load_ATOM_feed_data($content);
		foreach($content->entry as $item) {
			Debug::log('parsing item ' . var_export($item, true));
			$tmp_item = $this->parseItem($item);
			if (!empty($tmp_item)) {
				$this->items[] = $tmp_item;
			}
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	protected function RSS_2_0_time_to_timestamp($item){
		return DateTime::createFromFormat('D, d M Y H:i:s e', $item->pubDate)->getTimestamp();
	}

	// TODO set title, link, description, language, and so on
	protected function load_RSS_2_0_feed_data($rssContent){
		$this->name = trim((string)$rssContent->title);
		$this->uri = trim((string)$rssContent->link);
	}

	protected function load_ATOM_feed_data($content){
		$this->name = (string)$content->title;

		// Find best link (only one, or first of 'alternate')
		if(!isset($content->link)) {
			$this->uri = '';
		} elseif (count($content->link) === 1) {
			$this->uri = (string)$content->link[0]['href'];
		} else {
			$this->uri = '';
			foreach($content->link as $link) {
				if(strtolower($link['rel']) === 'alternate') {
					$this->uri = (string)$link['href'];
					break;
				}
			}
		}
	}

	protected function parseATOMItem($feedItem){
		// Some ATOM entries also contain RSS 2.0 fields
		$item = $this->parseRSS_2_0_Item($feedItem);

		if(isset($feedItem->id)) $item['uri'] = (string)$feedItem->id;
		if(isset($feedItem->title)) $item['title'] = (string)$feedItem->title;
		if(isset($feedItem->updated)) $item['timestamp'] = strtotime((string)$feedItem->updated);
		if(isset($feedItem->author)) $item['author'] = (string)$feedItem->author->name;
		if(isset($feedItem->content)) $item['content'] = (string)$feedItem->content;

		//When "link" field is present, URL is more reliable than "id" field
		if (count($feedItem->link) === 1) {
			$this->uri = (string)$feedItem->link[0]['href'];
		} else {
			foreach($feedItem->link as $link) {
				if(strtolower($link['rel']) === 'alternate') {
					$item['uri'] = (string)$link['href'];
					break;
				}
			}
		}

		return $item;
	}

	protected function parseRSS_0_9_1_Item($feedItem){
		$item = array();
		if(isset($feedItem->link)) $item['uri'] = (string)$feedItem->link;
		if(isset($feedItem->title)) $item['title'] = (string)$feedItem->title;
		// rss 0.91 doesn't support timestamps
		// rss 0.91 doesn't support authors
		// rss 0.91 doesn't support enclosures
		if(isset($feedItem->description)) $item['content'] = (string)$feedItem->description;
		return $item;
	}

	protected function parseRSS_1_0_Item($feedItem){
		// 1.0 adds optional elements around the 0.91 standard
		$item = $this->parseRSS_0_9_1_Item($feedItem);

		$namespaces = $feedItem->getNamespaces(true);
		if(isset($namespaces['dc'])) {
			$dc = $feedItem->children($namespaces['dc']);
			if(isset($dc->date)) $item['timestamp'] = strtotime((string)$dc->date);
			if(isset($dc->creator)) $item['author'] = (string)$dc->creator;
		}

		return $item;
	}

	protected function parseRSS_2_0_Item($feedItem){
		// Primary data is compatible to 0.91 with some additional data
		$item = $this->parseRSS_0_9_1_Item($feedItem);

		$namespaces = $feedItem->getNamespaces(true);
		if(isset($namespaces['dc'])) $dc = $feedItem->children($namespaces['dc']);
		if(isset($namespaces['media'])) $media = $feedItem->children($namespaces['media']);

		if(isset($feedItem->guid)) {
			foreach($feedItem->guid->attributes() as $attribute => $value) {
				if($attribute === 'isPermaLink'
					&& ($value === 'true' || (
							filter_var($feedItem->guid, FILTER_VALIDATE_URL)
							&& !filter_var($item['uri'], FILTER_VALIDATE_URL)
						)
					)
				) {
					$item['uri'] = (string)$feedItem->guid;
					break;
				}
			}
		}

		if(isset($feedItem->pubDate)) {
			$item['timestamp'] = strtotime((string)$feedItem->pubDate);
		} elseif(isset($dc->date)) {
			$item['timestamp'] = strtotime((string)$dc->date);
		}

		if(isset($feedItem->author)) {
			$item['author'] = (string)$feedItem->author;
		} elseif (isset($feedItem->creator)) {
			$item['author'] = (string)$feedItem->creator;
		} elseif(isset($dc->creator)) {
			$item['author'] = (string)$dc->creator;
		} elseif(isset($media->credit)) {
				$item['author'] = (string)$media->credit;
		}

		if(isset($feedItem->enclosure) && !empty($feedItem->enclosure['url'])) {
			$item['enclosures'] = array((string)$feedItem->enclosure['url']);
		}

		return $item;
	}

	/**
	 * Method should return, from a source RSS item given by lastRSS, one of our Items objects
	 * @param $item the input rss item
	 * @return a RSS-Bridge Item, with (hopefully) the whole content)
	 */
	protected function parseItem($item){
		switch($this->feedType) {
		case 'RSS_1_0':
			return $this->parseRSS_1_0_Item($item);
			break;
		case 'RSS_2_0':
			return $this->parseRSS_2_0_Item($item);
			break;
		case 'ATOM_1_0':
			return $this->parseATOMItem($item);
			break;
		default: returnClientError('Unknown version ' . $this->getInput('version') . '!');
		}
	}

	public function getURI(){
		return !empty($this->uri) ? $this->uri : parent::getURI();
	}

	public function getName(){
		return !empty($this->name) ? $this->name : parent::getName();
	}

	public function getIcon(){
		return !empty($this->icon) ? $this->icon : parent::getIcon();
	}
}
