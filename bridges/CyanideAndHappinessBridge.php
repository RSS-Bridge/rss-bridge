<?php
class CyanideAndHappinessBridge extends BridgeAbstract {
	const NAME = 'Cyanide & Happiness';
	const URI = 'https://explosm.net/';
	const DESCRIPTION = 'The Webcomic from Explosm.';
	const MAINTAINER = 'sal0max';
	const CACHE_TIMEOUT = 60 * 60 * 2; // 2 hours

	public function getIcon() {
		return self::URI . 'favicon-32x32.png';
	}

	public function getURI(){
		return self::URI . 'comics/latest#comic';
	}

	public function collectData() {
		$html = getSimpleHTMLDOM($this->getUri());

		foreach ($html->find('[class*=ComicImage]') as $element) {
			$date        = $element->find('[class^=Author__Right] p', 0)->plaintext;
			$author      = str_replace('by ', '', $element->find('[class^=Author__Right] p', 1)->plaintext);
			$image       = $element->find('img', 0)->src;
			$link        = $html->find('[rel=canonical]', 0)->href;

			$item = array(
				'uid'       => $link,
				'author'    => $author,
				'title'     => $date,
				'uri'       => $link . '#comic',
				'timestamp' => str_replace('.', '-', $date) . 'T00:00:00Z',
				'content'   => "<img src=\"$image\" />"
			);
			$this->items[] = $item;
		}
	}
}
