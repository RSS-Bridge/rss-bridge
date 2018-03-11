<?php
class RadioMelodieBridge extends BridgeAbstract {
	const NAME = 'Radio Melodie Actu';
	const URI = 'https://www.radiomelodie.com/';
	const DESCRIPTION = 'Retourne les actualités publiées par Radio Melodie';
	const MAINTAINER = 'sysadminstory';

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI . 'actu')
			or returnServerError('Could not request Radio Melodie.');
		$list = $html->find('div[class=actuitem]');
		foreach($list as $element) {
			$item = array();

			// Get picture URL
			$pictureHTML = $element->find('div[class=picture]');
			preg_match(
				'/background-image:url\((.*)\);/',
				$pictureHTML[0]->getAttribute('style'),
				$pictures);
			$pictureURL = $pictures[1];

			$item['enclosures'] = array($pictureURL);
			$item['uri'] = self::URI . $element->parent()->href;
			$item['title'] = $element->find('h3', 0)->plaintext;
			$item['content'] = $element->find('p', 0)->plaintext . '<br/><img src="'.$pictureURL.'"/>';
			$this->items[] = $item;
		}
	}
}
