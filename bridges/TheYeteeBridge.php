<?php
class TheYeteeBridge extends BridgeAbstract {

	const MAINTAINER = 'Monsieur Poutounours';
	const NAME = 'TheYetee';
	const URI = 'https://theyetee.com';
	const CACHE_TIMEOUT = 14400; // 4 h
	const DESCRIPTION = 'Fetch daily shirts from The Yetee';

	public function collectData(){

		$html = getSimpleHTMLDOM(self::URI)
			or returnServerError('Could not request The Yetee.');

		$div = $html->find('.hero-col');
		foreach($div as $element) {

				$item = array();
				$item['enclosures'] = array();

				$title = $element->find('h2', 0)->plaintext;
				$item['title'] = $title;

				$author = trim($element->find('div[class=credit]', 0)->plaintext);
				$item['author'] = $author;

				$uri = $element->find('div[class=controls] a', 0)->href;
				$item['uri'] = static::URI.$uri;

				$content = '<p>'.$element->find('section[class=product-listing-info] p', -1)->plaintext.'</p>';
				$photos = $element->find('a[class=js-modaal-gallery] img');
				foreach($photos as $photo) {
					$content = $content."<br /><img src='$photo->src' />";
					$item['enclosures'][] = $photo->src;
				}
				$item['content'] = $content;

				$this->items[] = $item;
		}
	}
}
