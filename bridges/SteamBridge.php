<?php
class SteamBridge extends BridgeAbstract {

	const NAME = 'Steam Bridge';
	const URI = 'https://steamcommunity.com/';
	const CACHE_TIMEOUT = 3600; // 1h
	const DESCRIPTION = 'Returns games list';
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
			'sort' => array(
				'name' => 'Sort by',
				'type' => 'list',
				'values' => array(
					'Rank' => 'rank',
					'Date Added' => 'added',
					'Name' => 'name',
					'Price' => 'price',
				)
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
			'sort' => $this->getInput('sort'),
			'cc' => $this->getInput('currency')
		);

		$url = self::URI . 'id/' . $username . '/wishlist?' . http_build_query($params);

		$html = '';
		$html = getSimpleHTMLDOM($url)
			or returnServerError("Could not request Steam Wishlist. Tried:\n - $url");

		foreach($html->find('#wishlist_items .wishlistRow') as $element) {

			$gameTitle = $element->find('h4', 0)->plaintext;
			$gameUri = $element->find('.storepage_btn_ctn a', 0)->href;
			$gameImg = $element->find('.gameListRowLogo img', 0)->src;

			$discountBlock = $element->find('.discount_block', 0);

			if($element->find('.discount_block', 0)) {
				$gameHasPromo = 1;
			} else {

				if($this->getInput('only_discount')) {
					continue;
				}

				$gameHasPromo = 0;

			}

			if($gameHasPromo) {

				$gamePromoValue = $discountBlock->find('.discount_pct', 0)->plaintext;
				$gameOldPrice = $discountBlock->find('.discount_original_price', 0)->plaintext;
				$gameNewPrice = $discountBlock->find('.discount_final_price', 0)->plaintext;
				$gamePrice = $gameNewPrice;

			} else {
				$gamePrice = $element->find('.gameListPriceData .price', 0)->plaintext;
			}

			$item = array();
			$item['uri'] = $gameUri;
			$item['title'] = $gameTitle;
			$item['price'] = $gamePrice;
			$item['hasPromo'] = $gameHasPromo;

			if($gameHasPromo) {

				$item['promoValue'] = $gamePromoValue;
				$item['oldPrice'] = $gameOldPrice;
				$item['newPrice'] = $gameNewPrice;

			}

			$this->items[] = $item;
		}
	}
}
