<?php
class RainbowSixSiegeBridge extends BridgeAbstract {

	const MAINTAINER = 'corenting';
	const NAME = 'Rainbow Six Siege Blog';
	const URI = 'https://rainbow6.ubisoft.com/siege/en-us/news/';
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'Latest articles from the Rainbow Six Siege blog';

	public function collectData(){
		$dlUrl = 'https://prod-tridionservice.ubisoft.com/live/v1/News/Latest?templateId=tcm%3A152-7677';
		$dlUrl .= '8-32&pageIndex=0&pageSize=10&language=en-US&detailPageId=tcm%3A152-194572-64';
		$dlUrl .= '&keywordList=175426&siteId=undefined&useSeoFriendlyUrl=true';
		$jsonString = getContents($dlUrl) or returnServerError('Error while downloading the website content');

		$json = json_decode($jsonString, true);
		$json = $json['items'];

		// Start at index 2 to remove highlighted articles
		for($i = 0; $i < count($json); $i++) {
			$jsonItem = $json[$i]['Content'];
			$article = str_get_html($jsonItem);

			$item = array();

			$uri = $article->find('h3 a', 0)->href;
			$uri = 'https://rainbow6.ubisoft.com' . $uri;
			$item['uri'] = $uri;
			$item['title'] = $article->find('h3', 0)->plaintext;
			$item['content'] = $article->find('img', 0)->outertext . '<br />' . $article->find('strong', 0)->plaintext;
			$item['timestamp'] = strtotime($article->find('p.news_date', 0)->plaintext);

			$this->items[] = $item;
		}
	}
}
