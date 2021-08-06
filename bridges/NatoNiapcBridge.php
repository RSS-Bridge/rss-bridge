<?php
class NatoNiapcBridge extends BridgeAbstract {

	const MAINTAINER = 'Cosmoken';
	const NAME = 'NATO Information Assurance Product Catalog';
	const URI = 'https://www.ia.nato.int';
	const CACHE_TIMEOUT = 3600; //1h
	const DESCRIPTION = 'Returns the N most recent products.';

	const PARAMETERS = array( array(
		'n' => array(
			'name' => 'number of products',
			'type' => 'number',
			'defaultValue' => 20,
			'exampleValue' => 10
		)
	));

	public function getIcon() {
		return self::URI . '/images/favicon.ico';
	}

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI . '/niapc')
			or returnServerError('Could not request Nato NIAPC.');

		$number = $this->getInput('n');

		/* number of products*/
		if(!empty($number)) {
			$num = min($number, 20);
		}

		$i=0;
		foreach($html->find('li.ResultItem') as $element) {
			$item = array();
			$item['uri'] = self::URI . $element->find('a',0)->href;

			$split = preg_split('/_/', $element->find('a',0)->href);
			$item['uid'] = $split[count($split)-1];

			$strDate = substr($element->find('div.ResultItemDate',0)->plaintext, 14, 20);

			$item['timestamp'] = strtotime($strDate);

			$extra = $this->getProductContent($item['uri']);
			$item['content'] = $extra['content'];
			$item['icon'] = $extra['icon'];
			$item['title'] = $element->find('a',0)->plaintext;
			$item['categories'] = $extra['categories'];
			$this->items[] = $item;

			if ($i > $num) {
				break;
			}
			$i++;
		}

	}

	private function getProductContent($uri) {
		$html = getSimpleHTMLDOM($uri)
			or returnServerError('Could not request : '.$uri);
		$item = Array();
		$item['content'] = $html->find('div.nia_section',0)->plaintext;
		$item['categories'] = Array($html->find('a.nia_link2',0)->plaintext);

		$imgSection = $html->find('div.DataView',0);
		if ($imgSection) {
			$item['icon'] = self::URI . $imgSection->find('img', 0)->src;
		}

		return $item;
	}
}
