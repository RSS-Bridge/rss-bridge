<?php
class ComboiosDePortugalBridge extends BridgeAbstract {
	const NAME = 'CP | Avisos';
	const BASE_URI = 'https://www.cp.pt';
	const URI = self::BASE_URI . '/passageiros/pt';
	const DESCRIPTION = 'Comboios de Portugal | Avisos';
	const MAINTAINER = 'somini';

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI() . '/consultar-horarios/avisos')
			or returnServerError('Could not load content');

		foreach($html->find('.warnings-table a') as $element) {
			$item = array();

			$item['title'] = $element->innertext;
			$item['uri'] = self::BASE_URI . implode('/', array_map('urlencode', explode('/', $element->href)));

			$this->items[] = $item;
		}
	}
}
