<?php

/**
 * RssBridgeWordpress
 * Returns the 3 newest full posts of a Wordpress blog
 *
 * @name Wordpress Bridge
* @homepage https://wordpress.com/
 * @description Returns the 3 newest full posts of a Wordpress blog
* @maintainer aledeg 
* @update 2014-05-26
 * @use1(url="blog URL (required)", name="blog name")
 */
class WordpressBridge extends BridgeAbstract {

	private $url;
	private $name;

	public function collectData(array $param) {
		$this->processParams($param);

		if (!$this->hasUrl()) {
			$this->returnError('You must specify a URL', 400);
		}

		$html = file_get_html($this->url) or $this->returnError("Could not request {$this->url}.", 404);

		if(!empty($html->find('.post')) ) {
			$i=0;
			foreach ($html->find('.post') as $article) {
				if($i < 3) {
					$uri = $article->find('a', 0)->href;
					$this->items[] = $this->getDetails($uri);
					$i++;
				}
			}
		}
		else {
			$this->returnError("Sorry, {$this->url} doesn't seem to be a Wordpress blog.", 404);
		}
	}

	private function getDetails($uri) {
		$html = file_get_html($uri) or exit;

		$item = new \Item();

		$article = $html->find('.post', 0);
		$item->uri = $uri;
		$item->title = $article->find('h1', 0)->innertext;
		$item->content = $this->clearContent($article->find('.entry-content,.entry', 0)->innertext);
		$item->timestamp = $this->getDate($uri);

		return $item;
	}

	private function clearContent($content) {
		$content = preg_replace('/<script.*\/script>/', '', $content);
		$content = preg_replace('/<div class="wpa".*/', '', $content);
		return $content;
	}

	private function getDate($uri) {
		preg_match('/\d{4}\/\d{2}\/\d{2}/', $uri, $matches);
		$date = new \DateTime($matches[0]);
		return $date->format('U');
	}

	public function getName() {
		return "{$this->name} - Wordpress Bridge";
	}

	public function getURI() {
		return $this->url;
	}

	public function getCacheDuration() {
		return 3600*3; // 3 hours
	}

	private function hasUrl() {
		if (empty($this->url)) {
			return false;
		}
		return true;
	}

	private function processParams($param) {
		$this->url = $param['url'];
		$this->name = $param['name'];
	}

}

