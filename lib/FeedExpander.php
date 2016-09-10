<?php
require_once(__DIR__ . '/BridgeInterface.php');
abstract class FeedExpander extends BridgeAbstract {

	private $name;
	private $uri;
	private $description;

	public function collectExpandableDatas($url, $maxItems = -1){
		if(empty($url)){
			$this->returnServerError('There is no $url for this RSS expander');
		}

		$this->debugMessage('Loading from ' . $url);

		/* Notice we do not use cache here on purpose:
		 * we want a fresh view of the RSS stream each time
		 */
		$content = $this->getContents($url)
			or $this->returnServerError('Could not request ' . $url);
		$rssContent = simplexml_load_string($content);

		$this->debugMessage('Detecting feed format/version');
		if(isset($rssContent->channel[0])){
			$this->debugMessage('Detected RSS format');
			if(isset($rssContent->item[0])){
				$this->debugMessage('Detected RSS 1.0 format');
				$this->collect_RSS_1_0_data($rssContent, $maxItems);
			} else {
				$this->debugMessage('Detected RSS 0.9x or 2.0 format');
				$this->collect_RSS_2_0_data($rssContent, $maxItems);
			}
		} elseif(isset($rssContent->entry[0])){
			$this->debugMessage('Detected ATOM format');
			$this->collect_ATOM_data($rssContent, $maxItems);
		} else {
			$this->debugMessage('Unknown feed format/version');
			$this->returnServerError('The feed format is unknown!');
		}
	}

	protected function collect_RSS_1_0_data($rssContent, $maxItems){
		$this->load_RSS_2_0_feed_data($rssContent->channel[0]);
		foreach($rssContent->item as $item){
			$this->debugMessage('parsing item ' . var_export($item, true));
			$this->items[] = $this->parseItem($item);
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	protected function collect_RSS_2_0_data($rssContent, $maxItems){
		$rssContent = $rssContent->channel[0];
		$this->debugMessage('RSS content is ===========\n'
		. var_export($rssContent, true)
		. '===========');

		$this->load_RSS_2_0_feed_data($rssContent);
		foreach($rssContent->item as $item){
			$this->debugMessage('parsing item ' . var_export($item, true));
			$this->items[] = $this->parseItem($item);
			if($maxItems !== -1 && count($this->items) >= $maxItems) break;
		}
	}

	protected function collect_ATOM_data($content, $maxItems){
		$this->load_ATOM_feed_data($content);
		foreach($content->entry as $item){
			$this->debugMessage('parsing item ' . var_export($item, true));
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
		$this->description = trim($rssContent->description);
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

		if(isset($content->subtitle))
			$this->description = $content->subtitle;
	}

	protected function parseATOMItem($feedItem){
		$item = array();
		if(isset($feedItem->id)) $item['uri'] = $feedItem->id;
		if(isset($feedItem->title)) $item['title'] = $feedItem->title;
		if(isset($feedItem->updated)) $item['timestamp'] = strtotime($feedItem->updated);
		if(isset($feedItem->author)) $item['author'] = $feedItem->author->name;
		if(isset($feedItem->content)) $item['content'] = $feedItem->content;
		return $item;
	}

	protected function parseRSS_0_9_1_Item($feedItem){
		$item = array();
		if(isset($feedItem->link)) $item['uri'] = $feedItem->link;
		if(isset($feedItem->title)) $item['title'] = $feedItem->title;
		// rss 0.91 doesn't support timestamps
		// rss 0.91 doesn't support authors
		if(isset($feedItem->description)) $item['content'] = $feedItem->description;
		return $item;
	}

	protected function parseRSS_1_0_Item($feedItem){
		// 1.0 adds optional elements around the 0.91 standard
		$item = $this->parseRSS_0_9_1_Item($feedItem);

		$namespaces = $feedItem->getNamespaces(true);
		if(isset($namespaces['dc'])){
			$dc = $feedItem->children($namespaces['dc']);
			if(isset($dc->date)) $item['timestamp'] = strtotime($dc->date);
			if(isset($dc->creator)) $item['author'] = $dc->creator;
		}

		return $item;
	}

	protected function parseRSS_2_0_Item($feedItem){
		// Primary data is compatible to 0.91 with some additional data
		$item = $this->parseRSS_0_9_1_Item($feedItem);

		$namespaces = $feedItem->getNamespaces(true);
		if(isset($namespaces['dc'])) $dc = $feedItem->children($namespaces['dc']);

		if(isset($feedItem->pubDate)){
			$item['timestamp'] = strtotime($feedItem->pubDate);
		} elseif(isset($dc->date)){
			$item['timestamp'] = strtotime($dc->date);
		}
		if(isset($feedItem->author)){
			$item['author'] = $feedItem->author;
		} elseif(isset($dc->creator)){
			$item['author'] = $dc->creator;
		}
		return $item;
	}

	/**
	 * Method should return, from a source RSS item given by lastRSS, one of our Items objects
	 * @param $item the input rss item
	 * @return a RSS-Bridge Item, with (hopefully) the whole content)
	 */
	abstract protected function parseItem($item);

	public function getURI(){
		return $this->uri;
	}

	public function getName(){
		return $this->name;
	}

	public function getDescription(){
		return $this->description;
	}
}
