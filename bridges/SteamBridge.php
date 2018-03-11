<?php
class SteamBridge extends BridgeAbstract {

	const NAME = 'Steam Bridge';
	const URI = 'https://store.steampowered.com/';
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
			'cc' => $this->getInput('currency'),
			'sort' => $this->getInput('sort')
		);

		$url = self::URI . 'wishlist/id/' . $username . '/?' . http_build_query($params);

		$jsonDataRegex = '/var g_rg(?:WishlistData|AppInfo) = ([^;]*)/';
		$content = getContents($url)
			or returnServerError("Could not request Steam Wishlist. Tried:\n - $url");

		preg_match_all($jsonDataRegex, $content, $matches, PREG_SET_ORDER, 0);

		$appList = json_decode($matches[0][1], true);
		$fullAppList = json_decode($matches[1][1], true);
		//var_dump($matches[1][1]);
		//var_dump($fullAppList);
		$sortedElementList = array_fill(0, count($appList), 0);
		foreach($appList as $app) {

			$sortedElementList[$app["priority"] - 1] = $app["appid"];

		}

		foreach($sortedElementList as $appId) {

			$app = $fullAppList[$appId];
			$gameTitle = $app["name"];
			$gameUri = "http://store.steampowered.com/app/" . $appId . "/";
			$gameImg = $app["capsule"];

			$item = array();
			$item['uri'] = $gameUri;
			$item['title'] = $gameTitle;

			if(count($app["subs"]) > 0) {
				if($app["subs"][0]["discount_pct"] != 0) {

					$item['promoValue'] = $app["subs"][0]["discount_pct"];
					$item['oldPrice'] = $app["subs"][0]["price"] / 100 / ((100 - $gamePromoValue / 100));
					$item['newPrice'] = $app["subs"][0]["price"] / 100;
					$item['price'] = $item['newPrice'];

					$item['hasPromo'] = true;

				} else {

					if($this->getInput('only_discount')) {
						continue;
					}

					$item['price'] = $app["subs"][0]["price"] / 100;
					$item['hasPromo'] = false;
				}

			}

			$this->items[] = $item;

		}

	}
}
