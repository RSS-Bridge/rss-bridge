<?php
class SteamBridge extends BridgeAbstract {

	const NAME = 'Steam Bridge';
	const URI = 'https://store.steampowered.com/';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns apps list';
	const MAINTAINER = 'jacknumber';
	const PARAMETERS = array(
		'Wishlist' => array(
			'username' => array(
				'name' => 'Username',
				'required' => true,
			),
			'currency' => array(
				'name' => 'Currency',
				'type' => 'list',
				'values' => array(
					// source: http://steam.steamlytics.xyz/currencies
					'USD' => 'us',
					'GBP' => 'gb',
					'EUR' => 'fr',
					'CHF' => 'ch',
					'RUB' => 'ru',
					'BRL' => 'br',
					'JPY' => 'jp',
					'SEK' => 'se',
					'IDR' => 'id',
					'MYR' => 'my',
					'PHP' => 'ph',
					'SGD' => 'sg',
					'THB' => 'th',
					'KRW' => 'kr',
					'TRY' => 'tr',
					'MXN' => 'mx',
					'CAD' => 'ca',
					'NZD' => 'nz',
					'CNY' => 'cn',
					'INR' => 'in',
					'CLP' => 'cl',
					'PEN' => 'pe',
					'COP' => 'co',
					'ZAR' => 'za',
					'HKD' => 'hk',
					'TWD' => 'tw',
					'SRD' => 'sr',
					'AED' => 'ae',
				),
			),
			'only_discount' => array(
				'name' => 'Only discount',
				'type' => 'checkbox',
			)
		)
	);

	public function collectData(){

		$username = $this->getInput('username');
		$params = array(
			'cc' => $this->getInput('currency')
		);

		$url = self::URI . 'wishlist/id/' . $username . '?' . http_build_query($params);

		$targetVariable = 'g_rgAppInfo';
		$sort = array();

		$html = '';
		$html = getSimpleHTMLDOM($url)
			or returnServerError("Could not request Steam Wishlist. Tried:\n - $url");

		$jsContent = $html->find('.responsive_page_template_content script', 0)->innertext;

		if(preg_match('/var ' . $targetVariable . ' = (.*?);/s', $jsContent, $matches)) {
			$appsData = json_decode($matches[1]);
		} else {
			returnServerError("Could not parse JS variable ($targetVariable) in page content.");
		}

		foreach($appsData as $id => $element) {

			$appType = $element->type;
			$appIsBuyable = 0;
			$appHasDiscount = 0;
			$appIsFree = 0;

			if($element->subs) {
				$appIsBuyable = 1;

				if($element->subs[0]->discount_pct) {

					$appHasDiscount = 1;
					$discountBlock = str_get_html($element->subs[0]->discount_block);
					$appDiscountValue = $discountBlock->find('.discount_pct', 0)->plaintext;
					$appOldPrice = $discountBlock->find('.discount_original_price', 0)->plaintext;
					$appNewPrice = $discountBlock->find('.discount_final_price', 0)->plaintext;
					$appPrice = $appNewPrice;

				} else {

					if($this->getInput('only_discount')) {
						continue;
					}

					$appPrice = $element->subs[0]->price / 100;
				}

			} else {

				if($this->getInput('only_discount')) {
					continue;
				}

				if(isset($element->free) && $element->free = 1) {
					$appIsFree = 1;
				}
			}

			$item = array();
			$item['uri'] = "http://store.steampowered.com/app/$id/";
			$item['title'] = $element->name;
			$item['type'] = $appType;
			$item['cover'] = str_replace('_292x136', '', $element->capsule);
			$item['timestamp'] = $element->added;
			$item['isBuyable'] = $appIsBuyable;
			$item['hasDiscount'] = $appHasDiscount;
			$item['isFree'] = $appIsFree;
			$item['priority'] = $element->priority;

			if($appIsBuyable) {
				$item['price'] = floatval(str_replace(',', '.', $appPrice));
			}

			if($appHasDiscount) {

				$item['discount']['value'] = $appDiscountValue;
				$item['discount']['oldPrice'] = floatval(str_replace(',', '.', $appOldPrice));
				$item['discount']['newPrice'] = floatval(str_replace(',', '.', $appNewPrice));

			}

			$item['enclosures'] = array();
			$item['enclosures'][] = str_replace('_292x136', '', $element->capsule);

			foreach($element->screenshots as $screenshot) {
				$item['enclosures'][] = substr($element->capsule, 0, -31) . $screenshot;
			}

			$sort[$id] = $element->priority;

			$this->items[] = $item;
		}

		array_multisort($sort, SORT_ASC, $this->items);
	}
}
