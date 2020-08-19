<?php
class EpicgamesBridge extends BridgeAbstract {

	const NAME = 'Epic Games Store News';
	const MAINTAINER = 'otakuf';
	const URI = 'https://www.epicgames.com';
	const DESCRIPTION = 'Returns the latest posts from epicgames.com';
	const CACHE_TIMEOUT = 3600; // 60min

	const PARAMETERS = array( array(
		'postcount' => array(
			'name' => 'Limit',
			'type' => 'list',
			'values' => array(
				'5' => 5,
				'10' => 10,
				'15' => 15,
				'20' => 20,
				'25' => 25,
			 ),
			'title' => 'Maximum number of items to return',
			'defaultValue' => 10,
		),
		'language' => array(
			'name' => 'Language',
			'type' => 'list',
			'values' => array(
				'English' => 'en',
				'العربية' => 'ar',
				'Deutsch' => 'de',
				'Español (Spain)' => 'es-ES',
				'Español (LA)' => 'es-MX',
				'Français' => 'fr',
				'Italiano' => 'it',
				'日本語' => 'ja',
				'한국어' => 'ko',
				'Polski' => 'pl',
				'Português (Brasil)' => 'pt-BR',
				'Русский' => 'ru',
				'ไทย' => 'th',
				'Türkçe' => 'tr',
				'简体中文' => 'zh-CN',
				'繁體中文' => 'zh-Hant',
			 ),
			'title' => 'Language of blog posts',
			'defaultValue' => 'en',
		),
	));

	public function collectData() {
		// Example: https://store-content.ak.epicgames.com/api/ru/content/blog?limit=25
		$api = 'https://store-content.ak.epicgames.com/api/';
		$url = $api . $this->getInput('language') . '/content/blog?limit=' . $this->getInput('postcount');

		$data = getContents($url)
			or returnServerError('Unable to get the news pages from epicgames.com!');
		$decodedData = json_decode($data);

		foreach($decodedData as $key => $value) {
			$item = array();
			$item['uri'] = self::URI . $value->url;
			$item['title'] = $value->title;
			$item['timestamp'] = $value->date;
			$item['author'] = 'Epic Games Store';
			if(!empty($value->author)) {
				$item['author'] = $value->author;
			}
			if(!empty($value->content)) {
				$item['content'] = defaultLinkTo($value->content, self::URI);
			}
			if(!empty($value->image)) {
				$item['enclosures'][] = $value->image;
			}
			$item['uid'] = $value->_id;
			$item['id'] = $value->_id;

			$this->items[] = $item;
		}
	}
}
