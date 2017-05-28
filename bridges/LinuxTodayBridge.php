<?php
class LinuxTodayBridge extends FeedExpander {
	
	const MAINTAINER = "duke";
	const NAME = "LinuxToday";
	const URI = "http://www.linuxtoday.com/";
	const DESCRIPTION = "Returns the daily posts from Linux Today (full text)";
	
	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI) or returnServerError('Could not request LinuxToday.');
		foreach ($html->find('div.index') as $element) {
			$item = array();
			$anav = $element->find('a.nav',0);
			$uri = $anav->href;
			$item['uri'] = $uri;
			$item['title'] = "<a href=".$uri."/>".$anav->find('strong',0)->innertext."</a>";
			$time = $element->find('span.sub',0)->innertext;
			$time = preg_split('/  +/',$time,-1);
			$item['timestamp'] = $time[0];
			$item['content'] = $element->find('p',0)->innertext;
			$this->items[] = $item;
		}
	}
}
