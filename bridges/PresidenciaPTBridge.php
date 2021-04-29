<?php
class PresidenciaPTBridge extends BridgeAbstract {
	const NAME = 'Presidência da República Portuguesa';
	const URI = 'https://www.presidencia.pt';
	const DESCRIPTION = 'Presidência da República Portuguesa | Mensagens';
	const MAINTAINER = 'somini';

	const PT_MONTH_NAMES = array(
		'janeiro',
		'fevereiro',
		'março',
		'abril',
		'maio',
		'junho',
		'julho',
		'agosto',
		'setembro',
		'outubro',
		'novembro',
		'dezembro');

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getURI() . '/atualidade/mensagens')
			or returnServerError('Could not load content');

		foreach($html->find('#atualidade-list article.card-block') as $element) {
			$item = array();

			$link = $element->find('a', 0);
			$etitle = $link->find('h2', 0);
			$edts = $element->find('p', 1);
			$edt = html_entity_decode($edts->innertext, ENT_HTML5);

			$item['title'] = $etitle->innertext;
			$item['uri'] = self::URI . $link->href;
			$item['description'] = $element;
			$item['timestamp'] = str_ireplace(
				array_map(function($name) { return ' de ' . $name . ' de '; }, self::PT_MONTH_NAMES),
				array_map(function($num) { return sprintf('-%02d-', $num); }, range(1, sizeof(self::PT_MONTH_NAMES))),
				$edt);

			$this->items[] = $item;
		}
	}
}
