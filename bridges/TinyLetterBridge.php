<?php
class TinyLetterBridge extends BridgeAbstract {
	const NAME = 'Tiny Letter';
	const URI = 'https://tinyletter.com/';
	const DESCRIPTION = 'Tiny Letter is a mailing list service';
	const MAINTAINER = 'somini';
	const PARAMETERS = array(
		array(
			'username' => array(
				'name' => 'User Name',
				'exampleValue' => 'forwards',
			)
		)
	);

	public function collectData() {
		$archives = self::getURI() . $this->getInput('username') . '/archive';
		$html = getSimpleHTMLDOMCached($archives)
			or returnServerError('Could not load content');

		foreach($html->find('.message-list li') as $element) {
			$item = array();

			$snippet = $element->find('p.message-snippet', 0);
			$link = $element->find('.message-link', 0);

			$item['title'] = $link->plaintext;
			$item['content'] = $snippet->innertext;
			$item['uri'] = $link->href;
			$item['timestamp'] = strtotime($element->find('.message-date', 0)->plaintext);

			$this->items[] = $item;
		}

	}
}
