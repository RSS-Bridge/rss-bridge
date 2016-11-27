<?php
class GizmodoBridge extends FeedExpander {

	const MAINTAINER = "polopollo";
	const NAME = "Gizmodo";
	const URI = "http://gizmodo.com/";
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = "Returns the newest posts from Gizmodo (full text).";

	protected function parseItem($item){
		$item = parent::parseItem($item);

		$articleHTMLContent = getSimpleHTMLDOMCached($item['uri']);
		if(!$articleHTMLContent){
			$text = 'Could not load '.$item['uri'];
		}else{
			$text = $articleHTMLContent->find('div.entry-content', 0)->innertext;
			foreach($articleHTMLContent->find('pagespeed_iframe') as $element) {
				$text .= '<p>link to a iframe (could be a video): <a href="'.$element->src.'">'.$element->src.'</a></p><br>';
			}

			$text = strip_tags($text, '<p><b><a><blockquote><img><em>');
		}

		$item['content'] = $text;
		return $item;
	}

	public function collectData(){
		$this->collectExpandableDatas('http://feeds.gawker.com/gizmodo/full');
	}
}
