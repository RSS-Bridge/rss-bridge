<?php
class ProxyBridge extends FeedExpander {
	const MAINTAINER = 'dhuschde';
	const NAME = 'RSS Feed Reader and Proxy';
	const URI = 'https://github.com/RSS-Bridge/rss-bridge';
	const DESCRIPTION = 'You can use this to read Feeds, cache them for one Hour or translate between Outputs';
	const PARAMETERS = array(
			array(
				'feed' => array(
					'name' => 'Feed URL',
					'type' => 'text',
					'required' => true,
					'title' => 'Insert your feed URL',
					'exampleValue' => 'https://myfeed.com'
				)
			)
		);
		public $feedname = '';

		private function feedName($f){
			$this->$feedname = $this->$feedname . ' / ' . $f;
		}

	protected function parseItem($item){
		$item = parent::parseItem($item);
		return $item;
	}

	public function collectData(){
		$this->collectExpandableDatas($this->getInput('feed'));
		$this->feedName(parent::getName());
	}

	public function getURI() {
		return self::URI;
	}




        public function getIcon() {
		$feed = $this->getInput('feed');
                $feedicon = 'https://www.google.com/s2/favicons?domain=' . $feed;
                return $feedicon;
        }




	public function getName() {
		if(isset($feedname)) {
			return '' . $this->$feedname;
		} else {
			return 'RSS Proxy';
		}
	}
}
