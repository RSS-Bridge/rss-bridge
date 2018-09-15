<?php

class AutoJMBridge extends BridgeAbstract {

	const NAME = 'AutoJM';
	const URI = 'http://www.autojm.fr/';
	const DESCRIPTION = 'Suivre les offres de véhicules proposés par AutoJM en fonction des critères de filtrages';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array(
		'Afficher les offres de véhicules disponible en fonction des critères du site AutoJM' => array(
			'url' => array(
				'name' => 'URL de la recherche',
				'type' => 'text',
				'required' => true,
				'title' => 'URL d\'une recherche avec filtre de véhicules sans le http://www.autojm.fr/',
				'exampleValue' => 'gammes/index/398?order_by=finition_asc&energie[]=3&transmission[]=2&dispo=all'
			)
		)
	);
	const CACHE_TIMEOUT = 3600;

	public function collectData() {
		$html = getSimpleHTMLDOM(self::URI . $this->getInput('url'))
			or returnServerError('Could not request AutoJM.');
		$list = $html->find('div[class*=ligne_modele]');
		foreach($list as $element) {
			$image = $element->find('img[class=width-100]', 0)->src;
			$serie = $element->find('div[class=serie]', 0)->find('span', 0)->plaintext;
			$url = $element->find('div[class=serie]', 0)->find('a[class=btn_ligne color-black]', 0)->href;
			if($element->find('div[class*=hasStock-info]', 0) != null) {
				$dispo = 'Disponible';
			} else {
				$dispo = 'Sur commande';
			}
			$carburant = str_replace('dispo |', '', $element->find('div[class=carburant]', 0)->plaintext);
			$transmission = $element->find('div[class*=bv]', 0)->plaintext;
			$places = $element->find('div[class*=places]', 0)->plaintext;
			$portes = $element->find('div[class*=nb_portes]', 0)->plaintext;
			$carosserie = $element->find('div[class*=coloris]', 0)->plaintext;
			$remise = $element->find('div[class*=remise]', 0)->plaintext;
			$prix = $element->find('div[class*=prixjm]', 0)->plaintext;

			$item = array();
			$item['uri'] = $url;
			$item['title'] = $serie;
			$item['content'] = '<p><img style="vertical-align:middle ; padding: 10px" src="' . $image . '" />'. $serie . '</p>';
			$item['content'] .= '<ul><li>Disponibilité : ' . $dispo . '</li>';
			$item['content'] .= '<li>Carburant : ' . $carburant . '</li>';
			$item['content'] .= '<li>Transmission : ' . $transmission . '</li>';
			$item['content'] .= '<li>Nombre de places : ' . $places . '</li>';
			$item['content'] .= '<li>Nombre de portes : ' . $portes . '</li>';
			$item['content'] .= '<li>Série : ' . $serie . '</li>';
			$item['content'] .= '<li>Carosserie : ' . $carosserie . '</li>';
			$item['content'] .= '<li>Remise : ' . $remise . '</li>';
			$item['content'] .= '<li>Prix : ' . $prix . '</li></ul>';

			$this->items[] = $item;
		}

	}
}
?>
