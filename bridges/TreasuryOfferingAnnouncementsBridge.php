<?php
class TreasuryOfferingAnnouncementsBridge extends FeedExpander {

	const MAINTAINER = 'Kevin Saylor';
	const NAME = 'Treasury Offering Announcements Bridge';
	const URI = 'https://www.treasurydirect.gov/TA_WS/securities/announced/rss';
	const DESCRIPTION = 'provides treasure auction results from US Treasury';
	const PARAMETERS = array();
	const CACHE_TIMEOUT = 3600;

	public function collectData(){
		$this->collectExpandableDatas(self::URI);
	}

	public function collectExpandableDatas($url, $maxItems = -1){
		if(empty($url)) {
			returnServerError('There is no $url for this RSS expander');
		}

		Debug::log('Loading from ' . $url);

		/* Notice we do not use cache here on purpose:
		 * we want a fresh view of the RSS stream each time
		 */
		$content = getContents($url, array(
			'Accept: 1/1,*/*'
		))
		or returnServerError('Could not request ' . $url);
		$rssContent = simplexml_load_string(trim($content));

		if ($rssContent === false) {
			throw new \Exception('Unable to parse string as xml');
		}

		Debug::log('Detecting feed format/version');
		switch(true) {
			case isset($rssContent->item[0]):
				Debug::log('Detected RSS 1.0 format');
				$this->feedType = self::FEED_TYPE_RSS_1_0;
				break;
			case isset($rssContent->channel[0]):
				Debug::log('Detected RSS 0.9x or 2.0 format');
				$this->feedType = self::FEED_TYPE_RSS_2_0;
				break;
			case isset($rssContent->entry[0]):
				Debug::log('Detected ATOM format');
				$this->feedType = self::FEED_TYPE_ATOM_1_0;
				break;
			default:
				Debug::log('Unknown feed format/version');
				returnServerError('The feed format is unknown!');
				break;
		}

		Debug::log('Calling function "collect_' . $this->feedType . '_data"');
		$this->{'collect_' . $this->feedType . '_data'}($rssContent, $maxItems);

		return $this;
	}

	/**
	 * Parse the contents of a single feed item, depending on the current feed
	 * type, into a RSS-Bridge item.
	 *
	 * @param object $item The current feed item
	 * @return object A RSS-Bridge item, with (hopefully) the whole content
	 */
	protected function parseItem($item){
		switch($this->feedType) {
			case self::FEED_TYPE_RSS_1_0:
				return $this->parseRSS_1_0_Item($item);
				break;
			case self::FEED_TYPE_RSS_2_0:
				return $this->parseRSS_2_0_Item($item);
				break;
			case self::FEED_TYPE_ATOM_1_0:
				return $this->parseATOMItem($item);
				break;
			default: returnClientError('Unknown version ' . $this->getInput('version') . '!');
		}
	}
}
