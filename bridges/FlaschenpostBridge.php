<?php
class FlaschenpostBridge extends BridgeAbstract {
	const NAME = 'Flaschenpost Bridge';
	const URI = 'https://www.flaschenpost.de/';
	const DESCRIPTION = 'Aktuelle Angebote auf Flaschenpost.de';
	const MAINTAINER = 'sal0max';
	const CACHE_TIMEOUT = 3600; // 1 hour
	const PARAMETERS = array(
		array(
			'zip-code' => array(
				'name' => 'Postleitzahl',
				'type' => 'text',
				'required' => true,
				'exampleValue' => '80333',
				// https://stackoverflow.com/a/7926743/421140
				'pattern' => '^(?!01000|99999)(0[1-9]\d{3}|[1-9]\d{4})$',
			),
			'water' => array(
				'name' => 'Wasser',
				'type' => 'checkbox',
			),
			'beer' => array(
				'name' => 'Bier',
				'type' => 'checkbox',
			),
			'lemonade' => array(
				'name' => 'Limonade',
				'type' => 'checkbox',
			),
			'juice' => array(
				'name' => 'Saft & Schorle',
				'type' => 'checkbox',
			),
			'wine' => array(
				'name' => 'Wein & Mehr',
				'type' => 'checkbox',
			),
			'liquor' => array(
				'name' => 'Spirituosen',
				'type' => 'checkbox',
			),
			'food' => array(
				'name' => 'Lebensmittel',
				'type' => 'checkbox',
			),
			'household' => array(
				'name' => 'Haushalt',
				'type' => 'checkbox',
			),
		)
	);

	public function getIcon() {
		return 'https://image.flaschenpost.de/CI/fp-favicon.png';
	}

	public function getName(): string {
		$categories = array();
		if ($this->getInput('water'))
			$categories[] = 'Wasser';
		if ($this->getInput('beer'))
			$categories[] = 'Bier';
		if ($this->getInput('lemonade'))
			$categories[] = 'Limonade';
		if ($this->getInput('juice'))
			$categories[] = 'Saft & Schorle';
		if ($this->getInput('wine'))
			$categories[] = 'Wein & Mehr';
		if ($this->getInput('liquor'))
			$categories[] = 'Spirituosen';
		if ($this->getInput('food'))
			$categories[] = 'Lebensmittel';
		if ($this->getInput('household'))
			$categories[] = 'Haushalt';
		if (empty($categories)) {
			return $this::NAME;
		} else {
			return $this::NAME . ' – ' . implode(', ', $categories);
		}
	}

	public function collectData() {
		// which categories to include
		$urls = array();
		if ($this->getInput('water'))
			array_push($urls,
				'wasser/spritzig',
				'wasser/medium',
				'wasser/still',
				'wasser/aromatisiert',
				'wasser/heilwasser',
				'wasser/bio-wasser',
				'wasser/gourmet'
			);
		if ($this->getInput('beer'))
			array_push($urls,
				'bier/alkoholfrei',
				'bier/biermischgetraenke',
				'bier/craft-beer',
				'bier/export-lager-maerzen',
				'bier/helles',
				'bier/internationale-biere',
				'bier/koelsch',
				'bier/land-kellerbier',
				'bier/malzbier',
				'bier/pils',
				'bier/radler',
				'bier/spezialitaeten',
				'bier/weizen-weissbier'
			);
		if ($this->getInput('lemonade'))
			array_push($urls,
				'limonade/cola',
				'limonade/orangenlimonade',
				'limonade/zitronenlimonade',
				'limonade/cola-mix',
				'limonade/teegetraenke',
				'limonade/fassbrause',
				'limonade/mate',
				'limonade/bio',
				'limonade/zum-mixen',
				'limonade/sonstige-limos'
			);
		if ($this->getInput('juice'))
			array_push($urls,
				'saft-und-schorle/apfelsaft',
				'saft-und-schorle/apfelschorle',
				'saft-und-schorle/orangensaft',
				'saft-und-schorle/multivitaminsaft',
				'saft-und-schorle/maracujasaft',
				'saft-und-schorle/traubensaft',
				'saft-und-schorle/johannisbeersaft',
				'saft-und-schorle/rhabarbersaft',
				'saft-und-schorle/rhabarberschorle',
				'saft-und-schorle/kirschsaft',
				'saft-und-schorle/sonstige-saefte',
				'saft-und-schorle/sonstige-schorlen'
			);
		if ($this->getInput('wine'))
			array_push($urls,
				'wein-und-mehr/weisswein',
				'wein-und-mehr/rotwein',
				'wein-und-mehr/rose',
				'wein-und-mehr/bio-wein',
				'wein-und-mehr/sonstige-weine',
				'wein-und-mehr/sekt-mehr',
				'wein-und-mehr/probierpakete',
				'wein-und-mehr/gluehwein'
			);
		if ($this->getInput('liquor'))
			array_push($urls,
				'spirituosen/wodka',
				'spirituosen/gin',
				'spirituosen/whisky',
				'spirituosen/rum',
				'spirituosen/weitere-spirituosen',
				'spirituosen/kraeuterlikoer',
				'spirituosen/weitere-likoere',
				'spirituosen/aperitif'
			);
		if ($this->getInput('food'))
			array_push($urls,
				'lebensmittel/veggie-vegan',
				'lebensmittel/kaffee-tee',
				'lebensmittel/milch-alternativen',
				'lebensmittel/tiefkuehltruhe',
				'lebensmittel/nuesse-trockenobst',
				'lebensmittel/suesses-salziges',
				'lebensmittel/nudeln-reis-getreide',
				'lebensmittel/fertiges-konserven',
				'lebensmittel/sossen-oele-gewuerze'
			);
		if ($this->getInput('household'))
			array_push($urls,
				'haushalt/hygieneartikel',
				'haushalt/gesundheit-verhuetung',
				'haushalt/kueche',
				'haushalt/haushaltsartikel',
				'haushalt/spuelen-reinigen',
				'haushalt/waschen'
			);

		// start scraping
		foreach ($urls as $url) {
			try {
				$html = getSimpleHTMLDOM(
					self::URI
					. $url
					. "?plz={$this->getInput('zip-code')}"
				);
			} catch (\Exception $ex) {
				// this url is currently not available: skip it
				continue;
			}

			// extract the JavaScript block which contains all the data we need
			$regex = '/(\{childElements:\[.*\})\];/';
			preg_match($regex, $html, $matches);
			$js = $matches[1];

			// convert JavaScript to JSON
			$js = $this->jsToJson($js);

			// get all products
			$products = $this->recursiveFind((array)json_decode($js), 'products');
			foreach ($products as $product) {
				// there can be multiple variants, like 0.5l and 0.33l bottles
				foreach ($product->product->articles as $article) {
					$this->addArticle($article, $product->product);
				}
			}
		}
	}

	private function jsToJson(string $js): string {
		// remove all html
		$js = strip_tags($js);
		// escape double quotes
		$js = str_replace('"', '\\"', $js);
		// add double quotes to all keys
		$js = preg_replace('/(?<=[,{])(\w+)(?=:)/', '"$1"', $js);
		// replace all single quotes with double quotes at all values
		$js = str_replace('\'', '"', $js);
		// sometimes, there are more than one JSON blocks; we're interested in the first one
		$js = $this->splitJsonObjects($js)[0];
		return $js;
	}

	private function addArticle($article, $product) {
		$regularPrice = $article->trackingDefaultPrice;
		$discountPrice = $article->crossedPrice;
		$discount = round((($regularPrice - $discountPrice) / $regularPrice) * 100.0);
		$regularPriceString = $article->defaultPrice;
		$discountPriceString = $article->price;

		// only discounted products
		if ($regularPrice != $discountPrice) {
			$name = str_replace('"', '\'', $product->name);
			$imageUrl = 'https://image.flaschenpost.de/cdn-cgi/image/width=120,height=120,q=50/articles/small/'
				. $article->articleId . '.png';
			$pricePerUnit = str_replace(['(', ')'], '', $article->pricePerUnit);
			$deposit = $article->deposit ? "Pfand: $article->deposit" : 'Pfandfrei';
			$alcohol = $product->alcoholInfo ? str_replace(['enthält', 'Vol.-', 'Alkohol'], '', $product->alcoholInfo)
				. ' Alkohol' : '';
			$description = <<<EOD
<div style="padding: 20px; display: flex;">
<img src="{$imageUrl}" alt="{$name}" style="float: left; margin-right: 35px;"/>
<p style="display: inline-block; align-self: center; line-height: 1.5rem;">
$pricePerUnit
<br>
{$article->shortDescription}
<br>
$deposit
<br>
$alcohol
</p>
</div>
EOD;

			$item['title'] = "$name: $discountPriceString statt $regularPriceString (-$discount\u{2009}%)";
			$item['content'] = $description;
			// use current date (@midnight) as timestamp
			$item['timestamp'] = \DateTime::createFromFormat('d.m.y', date('d.m.y'))
				->setTimezone(new \DateTimeZone('Europe/Berlin'))
				->setTime(0, 0)
				->getTimestamp();
			$item['uri'] = urljoin(
				'https://www.flaschenpost.de/',
				"{$product->brandWebShopUrl}/{$product->webShopUrl}"
			);
			// use "name-<timestamp>" as uid; that way, there's a new entry each day, when a product stays discounted
			$item['uid'] = $name . '-' . $item['timestamp'];

			// only add if unique
			$exists = false;
			foreach ($this->items as $i) {
				if ($i['uri'] === $item['uri']) {
					$exists = true;
					break;
				}
			}
			if (!$exists) {
				$this->items[] = $item;
			}
		}
	}

	// https://stackoverflow.com/a/3975706/421140
	private function recursiveFind(array $haystack, $needle) {
		$iterator = new \RecursiveArrayIterator($haystack);
		$recursive = new \RecursiveIteratorIterator(
			$iterator,
			\RecursiveIteratorIterator::SELF_FIRST
		);
		foreach ($recursive as $key => $value) {
			if ($key === $needle) {
				return $value;
			}
		}
		return null;
	}

	/**
	 * http://ryanuber.com/07-31-2012/split-and-decode-json-php.html
	 *
	 * json_split_objects - Return an array of many JSON objects
	 *
	 * In some applications (such as PHPUnit, or salt), JSON output is presented as multiple
	 * objects, which you cannot simply pass in to json_decode(). This function will split
	 * the JSON objects apart and return them as an array of strings, one object per indice.
	 *
	 * @param string $json The JSON data to parse
	 *
	 * @return array (of strings)
	 */
	private function splitJsonObjects(string $json): array {
		$q = FALSE;
		$len = strlen($json);
		for ($l = $c = $i = 0; $i < $len; $i++) {
			$json[$i] == '"' && ($i > 0 ? $json[$i - 1] : '') != '\\' && $q = !$q;
			if (!$q && in_array($json[$i], array(" ", "\r", "\n", "\t"))) {
				continue;
			}
			in_array($json[$i], array('{', '[')) && !$q && $l++;
			in_array($json[$i], array('}', ']')) && !$q && $l--;
			(isset($objects[$c]) && $objects[$c] .= $json[$i]) || $objects[$c] = $json[$i];
			$c += ($l == 0);
		}
		return isset($objects) ? $objects : array();
	}

}
