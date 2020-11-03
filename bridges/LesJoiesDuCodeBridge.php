<?php
class LesJoiesDuCodeBridge extends BridgeAbstract {

	const MAINTAINER = 'superbaillot.net';
	const NAME = 'Les Joies Du Code';
	const URI = 'https://lesjoiesducode.fr/';
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = 'LesJoiesDuCode';

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request LesJoiesDuCode.');

		foreach($html->find('article.blog-post') as $element) {
			$item = array();
			$temp = $element->find('h1 a', 0);
			$titre = html_entity_decode($temp->innertext);
			$url = $temp->href;

			$temp = $element->find('div.blog-post-content', 0);

			// retrieve .gif instead of static .jpg
			$images = $temp->find('p img');
			foreach($images as $image) {
				$img_src = str_replace('.jpg', '.gif', $image->src);
				$image->src = $img_src;
			}
			$content = $temp->innertext;

			$item['content'] = trim($content);
			$item['uri'] = $url;
			$item['title'] = trim($titre);

			$this->items[] = $item;
		}
	}
}
