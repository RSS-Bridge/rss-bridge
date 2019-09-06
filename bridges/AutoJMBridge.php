<?php

class AutoJMBridge extends BridgeAbstract {

	const NAME = 'AutoJM';
	const URI = 'https://www.autojm.fr/';
	const DESCRIPTION = 'Suivre les offres de véhicules proposés par AutoJM en fonction des critères de filtrages';
	const MAINTAINER = 'sysadminstory';
	const PARAMETERS = array(
		'Afficher les offres de véhicules disponible en fonction des critères du site AutoJM' => array(
			'url' => array(
				'name' => 'URL du modèle',
				'type' => 'text',
				'required' => true,
				'title' => 'URL d\'une recherche avec filtre de véhicules sans le http://www.autojm.fr/',
				'exampleValue' => 'achat-voitures-neuves-peugeot-nouvelle-308-5p'
			),
			'energy' => array(
				'name' => 'Carburant',
				'type' => 'list',
				'values' => array(
					'-' => '',
					'Diesel' => 1,
					'Essence' => 3,
					'Hybride' => 5
				),
				'title' => 'Carburant'
			),
			'transmission' => array(
				'name' => 'Transmission',
				'type' => 'list',
				'values' => array(
					'-' => '',
					'Automatique' => 1,
					'Manuelle' => 2
				),
				'title' => 'Transmission'
			),
			'priceMin' => array(
				'name' => 'Prix minimum',
				'type' => 'number',
				'required' => false,
				'title' => 'Prix minimum du véhicule',
				'exampleValue' => '10000',
				'defaultValue' => '0'
			),
			'priceMax' => array(
				'name' => 'Prix maximum',
				'type' => 'number',
				'required' => false,
				'title' => 'Prix maximum du véhicule',
				'exampleValue' => '15000',
				'defaultValue' => '150000'
			)
		)
	);
	const CACHE_TIMEOUT = 3600;

	public function getIcon() {
		return self::URI . 'favicon.ico';
	}

	public function getName() {
		switch($this->queriedContext) {
		case 'Afficher les offres de véhicules disponible en fonction des critères du site AutoJM':
			$html = getSimpleHTMLDOMCached(self::URI . $this->getInput('url'), 86400);
			$name = html_entity_decode($html->find('title', 0)->plaintext);
			return $name;
			break;
		default:
			return parent::getName();
		}

	}

	public function collectData() {

		$model_url = self::URI . $this->getInput('url');

		// Get the session cookies and the form token
		$this->getInitialParameters($model_url);

		// Build the form
		$post_data = array(
			'form[energy]' => $this->getInput('energy'),
			'form[transmission]' => $this->getInput('transmission'),
			'form[priceMin]' => $this->getInput('priceMin'),
			'form[priceMin]' => $this->getInput('priceMin'),
			'form[_token]' => $this->token
		);

		// Set the Form request content type
		$header = array(
			'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
		);

		// Set the curl options (POST query and content, and session cookies
		$curl_opts = array(
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => http_build_query($post_data),
			CURLOPT_COOKIE => $this->cookies
		);

		// Get the JSON content of the form
		$json = getContents($model_url, $header, $curl_opts)
			or returnServerError('Could not request AutoJM.');

		// Extract the HTML content from the JSON result
		$data = json_decode($json);
		$html = str_get_html($data->content);

		// Go through every finisha of the model
		$list = $html->find('h3');
		foreach ($list as $finish) {
			$finish_name = $finish->plaintext;
			$motorizations = $finish->next_sibling()->find('li');
			foreach ($motorizations as $element) {
				$image = $element->find('div[class=block-product-image]', 0)->{'data-ga-banner'};
				$serie = $element->find('span[class=model]', 0)->plaintext;
				$url = self::URI . substr($element->find('a', 0)->href, 1);
				if ($element->find('span[class*=block-product-nbModel]', 0) != null) {
					$availability = 'En Stock';
				} else {
					$availability = 'Sur commande';
				}
				$discount_html = $element->find('span[class*=tag--promo]', 0);
				if ($discount_html != null) {
					$discount = $discount_html->plaintext;
				} else {
					$discount = 'inconnue';
				}
				$price = $element->find('span[class=price red h1]', 0)->plaintext;
				$item = array();
				$item['title'] = $finish_name . ' ' . $serie;
				$item['content'] = '<p><img style="vertical-align:middle ; padding: 10px" src="' . $image . '" />'
					. $finish_name . ' ' . $serie . '</p>';
				$item['content'] .= '<ul><li>Disponibilité : ' . $availability . '</li>';
				$item['content'] .= '<li>Série : ' . $serie . '</li>';
				$item['content'] .= '<li>Remise : ' . $discount . '</li>';
				$item['content'] .= '<li>Prix : ' . $price . '</li></ul>';

				// Add a fictionnal anchor to the RSS element URL, based on the item content ;
				// As the URL could be identical even if the price change, some RSS reader will not show those offers as new items
				$item['uri'] = $url . '#' . md5($item['content']);

				$this->items[] = $item;
			}
		}
	}

	/**
	 * Gets the session cookie and the form token
	 *
	 * @param string $pageURL The URL from which to get the values
	 */
	private function getInitialParameters($pageURL) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $pageURL);
		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$data = curl_exec($ch);

		// Separate the response header and the content
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$header = substr($data, 0, $headerSize);
		$content = substr($data, $headerSize);
		curl_close($ch);

		// Extract the cookies from the headers
		$cookies = '';
		$http_response_header = explode("\r\n", $header);
		foreach ($http_response_header as $hdr) {
			if (strpos($hdr, 'Set-Cookie') !== false) {
				$cLine = explode(':', $hdr)[1];
				$cLine = explode(';', $cLine)[0];
				$cookies .= ';' . $cLine;
			}
		}
		$this->cookies = trim(substr($cookies, 1));

		// Get the token from the content
		$html = str_get_html($content);
		$token = $html->find('input[type=hidden][id=form__token]', 0);
		$this->token = $token->value;
	}
}
