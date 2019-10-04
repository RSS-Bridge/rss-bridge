<?php
class YahtzeeDevDiaryBridge extends BridgeAbstract {
	const MAINTAINER = 'somini';
	const NAME = "Yahtzee's Dev Diary";
	const URI = 'https://www.escapistmagazine.com/v2/yahtzees-dev-diary-completed-games-list/';
	const DESCRIPTION = 'Yahtzee’s Dev Diary Series';

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not load content');

		foreach($html->find('blockquote.wp-embedded-content a') as $element) {
			$item = array();

			$item['title'] = $element->innertext;
			$item['uri'] = $element->href;

			$this->items[] = $item;
		}
	}
}
