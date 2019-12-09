<?php
class ScoopItBridge extends BridgeAbstract {

	const MAINTAINER = 'Pitchoule';
	const NAME = 'ScoopIt';
	const URI = 'https://www.scoop.it/';
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = 'Returns most recent results from ScoopIt.';

	const PARAMETERS = array( array(
		'u' => array(
			'name' => 'keyword',
			'required' => true
		)
	));

	public function collectData(){
		$this->request = $this->getInput('u');
		$link = self::URI . 'search?q=' . urlencode($this->getInput('u'));

		$html = getSimpleHTMLDOM($link)
			or returnServerError('Could not request ScoopIt. for : ' . $link);

		foreach($html->find('div.post-view') as $element) {
			$item = array();
			$item['uri'] = $element->find('a', 0)->href;
			$item['title'] = preg_replace(
				'~[[:cntrl:]]~',
				'',
				$element->find('div.tCustomization_post_title', 0)->plaintext
			);

			$item['content'] = preg_replace(
				'~[[:cntrl:]]~',
				'',
				$element->find('div.tCustomization_post_description', 0)->plaintext
			);

			$this->items[] = $item;
		}
	}
}
