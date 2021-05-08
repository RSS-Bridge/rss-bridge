<?php
class PresidenciaPTBridge extends BridgeAbstract {
	const NAME = 'Presidência da República Portuguesa';
	const URI = 'https://www.presidencia.pt';
	const DESCRIPTION = 'Presidência da República Portuguesa';
	const MAINTAINER = 'somini';
	const PARAMETERS = array(
		'Section' => array(
			'/atualidade/noticias' => array(
				'name' => 'Notícias',
				'type' => 'checkbox',
				'defaultValue' => 'checked',
			),
			'/atualidade/mensagens' => array(
				'name' => 'Mensagens',
				'type' => 'checkbox',
				'defaultValue' => 'checked',
			),
			'/atualidade/atividade-legislativa' => array(
				'name' => 'Atividade Legislativa',
				'type' => 'checkbox',
				'defaultValue' => 'checked',
			),
			'/atualidade/notas-informativas' => array(
				'name' => 'Notas Informativas',
				'type' => 'checkbox',
				'defaultValue' => 'checked',
			)
		)
	);

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

	public function getIcon(){
		return 'https://www.presidencia.pt/Theme/favicon/apple-touch-icon.png';
	}

	public function collectData() {
		foreach(array_keys($this->getParameters()['Section']) as $k) {
			Debug::log('Key: ' . var_export($k, true));
			if($this->getInput($k)) {
				$html = getSimpleHTMLDOMCached($this->getURI() . $k);

				foreach($html->find('#atualidade-list article.card-block') as $element) {
					$item = array();

					$link = $element->find('a', 0);
					$etitle = $element->find('.content-box h2', 0);
					$edts = $element->find('p', 1);
					$edt = html_entity_decode($edts->innertext, ENT_HTML5);

					$item['title'] = strip_tags($etitle->innertext);
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
	}
}
