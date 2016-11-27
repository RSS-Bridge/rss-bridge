<?php
require_once(__DIR__ . '/BridgeInterface.php');
abstract class FeedExpander extends BridgeAbstract {

	private $name;
	private $uri;
	private $feedType;

	public function collectExpandableDatas($url, $maxItems = -1){
		if(empty($url)){
			returnServerError('There is no $url for this RSS expander');
		}

		debugMessage('Loading from ' . $url);

		/* Notice we do not use cache here on purpose:
		 * we want a fresh view of the RSS stream each time
		 */
		$content = getContents($url)
			or returnServerError('Could not request ' . $url);
		$rssContent = simplexml_load_string($content);

		debugMessage('Detecting feed format/version');
		switch(true){
		case isset($rssContent->item[0]):
			debugMessage('Detected RSS 1.0 format');
			$this->feedType = "RSS_1_0";
			break;
		case isset($rssContent->channel[0]):
			debugMessage('Detected RSS 0.9x or 2.0 format');
			$this->feedType = "RSS_2_0";
			break;
		case isset($rssContent->entry[0]):
			debugMessage('Detected ATOM format');
			$this->feedType = "ATOM_1_0";
			break;
		default:
			debugMessage('Unknown feed format/version');
			returnServerError('The feed format is unknown!');
			break;
		}

		debugMessage('Calling function "collect_' . $this->feedType . '_data"');
		$this->{'collect_' . $this->feedType . '_data'}($rssContent, $maxItems);
	}

	protected function collect_RSS_1_0_data($rssContent, $maxItems){
		$this->load_RSS_2_0_feed_data($rssContent->channel[0]);
		foreach($rssContent->item as $item){
			debugMessage('parsing item ' . var_export($item, true));
			$this->items[] = $this->parseItem($item);
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	protected function collect_RSS_2_0_data($rssContent, $maxItems){
		$rssContent = $rssContent->channel[0];
		debugMessage('RSS content is ===========\n'
		. var_export($rssContent, true)
		. '===========');

		$this->load_RSS_2_0_feed_data($rssContent);
		foreach($rssContent->item as $item){
			debugMessage('parsing item ' . var_export($item, true));
			$this->items[] = $this->parseItem($item);
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	protected function collect_ATOM_1_0_data($content, $maxItems){
		$this->load_ATOM_feed_data($content);
		foreach($content->entry as $item){
			debugMessage('parsing item ' . var_export($item, true));
			$this->items[] = $this->parseItem($item);
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	protected function RSS_2_0_time_to_timestamp($item){
		return DateTime::createFromFormat('D, d M Y H:i:s e', $item->pubDate)->getTimestamp();
	}

	// TODO set title, link, description, language, and so on
	protected function load_RSS_2_0_feed_data($rssContent){
		$this->name = trim($rssContent->title);
		$this->uri = trim($rssContent->link);
	}

	protected function load_ATOM_feed_data($content){
		$this->name = $content->title;

		// Find best link (only one, or first of 'alternate')
		if(!isset($content->link)){
			$this->uri = '';
		} elseif (count($content->link) === 1){
			$this->uri = $content->link[0]['href'];
		} else {
			$this->uri = '';
			foreach($content->link as $link){
				if(strtolower($link['rel']) === 'alternate'){
					$this->uri = $link['href'];
					break;
				}
			}
		}
	}

	protected function parseATOMItem($feedItem){
		$item = array();
		if(isset($feedItem->id)) $item['uri'] = (string)$feedItem->id;
		if(isset($feedItem->title)) $item['title'] = (string)$feedItem->title;
		if(isset($feedItem->updated)) $item['timestamp'] = strtotime((string)$feedItem->updated);
		if(isset($feedItem->author)) $item['author'] = (string)$feedItem->author->name;
		if(isset($feedItem->content)) $item['content'] = (string)$feedItem->content;
		return $item;
	}

	protected function parseRSS_0_9_1_Item($feedItem){
		$item = array();
		if(isset($feedItem->link)) $item['uri'] = (string)$feedItem->link;
		if(isset($feedItem->title)) $item['title'] = (string)$feedItem->title;
		// rss 0.91 doesn't support timestamps
		// rss 0.91 doesn't support authors
		if(isset($feedItem->description)) $item['content'] = (string)$feedItem->description;
		return $item;
	}

	protected function parseRSS_1_0_Item($feedItem){
		// 1.0 adds optional elements around the 0.91 standard
		$item = $this->parseRSS_0_9_1_Item($feedItem);

		$namespaces = $feedItem->getNamespaces(true);
		if(isset($namespaces['dc'])){
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

		if(isset($feedItem->guid)){
			foreach($feedItem->guid->attributes() as $attribute => $value){
				if($attribute === 'isPermaLink'
				&& ($value === 'true' || filter_var($feedItem->guid,FILTER_VALIDATE_URL))){
					$item['uri'] = (string)$feedItem->guid;
					break;
				}
			}
		}

		if(isset($feedItem->pubDate)){
			$item['timestamp'] = strtotime((string)$feedItem->pubDate);
		} elseif(isset($dc->date)){
			$item['timestamp'] = strtotime((string)$dc->date);
		}
		if(isset($feedItem->author)){
			$item['author'] = (string)$feedItem->author;
		} elseif(isset($dc->creator)){
			$item['author'] = (string)$dc->creator;
		}
		return $item;
	}

	/**
	 * Method should return, from a source RSS item given by lastRSS, one of our Items objects
	 * @param $item the input rss item
	 * @return a RSS-Bridge Item, with (hopefully) the whole content)
	 */
	protected function parseItem($item){
		switch($this->feedType){
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
		return $this->uri;
	}

	public function getName(){
		return $this->name;
	}
}
