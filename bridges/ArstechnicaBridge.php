<?php
class ArstechnicaBridge extends FeedExpander {

	const MAINTAINER = "prysme";
	const NAME = "ArstechnicaBridge";
	const URI = "http://arstechnica.com";
	const DESCRIPTION = "The PC enthusiast's resource. Power users and the tools they love, without computing religion";

	protected function parseItem($item){
		$item = parent::parseItem($item);

		$html = $this->getSimpleHTMLDOMCached($item['uri']);
		if(!$html){
			$item['content'] .= '<p>Requesting full article failed.</p>';
		}else{
			$item['content'] = $html->find('.article-guts', 0);
		}

		return $item;
	}

	public function collectData(){
		$this->collectExpandableDatas('http://feeds.arstechnica.com/arstechnica/index/');
	}

	public function getCacheDuration() {
		return 7200; // 2h
	}

}
