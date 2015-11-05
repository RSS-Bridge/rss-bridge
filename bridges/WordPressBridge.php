<?php
class WordPressBridge extends BridgeAbstract {

	private $url;
	public $name;

	public function loadMetadatas() {

		$this->maintainer = "aledeg";
		$this->name = "Wordpress Bridge";
		$this->uri = "https://wordpress.com/";
		$this->description = "Returns the 3 newest full posts of a Wordpress blog";
		$this->update = "2015-09-05";

		$this->parameters[] =
		'[
			{
				"name" : "blog URL",
				"required" : "true",
				"identifier" : "url"
			},
			{
				"name" : "Blog name",
				"identifier" : "name"
			}
		]';

	}

	public function collectData(array $param) {
		$this->processParams($param);

		if (!$this->hasUrl()) {
			$this->returnError('You must specify a URL', 400);
		}

		$html = file_get_html($this->url) or $this->returnError("Could not request {$this->url}.", 404);
		$posts = $html->find('.post');

		if(!empty($posts) ) {
			$i=0;
			foreach ($html->find('.post') as $article) {
				if($i < 3) {
					$uri = $article->find('a', 0)->href;
					$thumbnail = $article->find('img', 0)->src;
					$this->items[] = $this->getDetails($uri, $thumbnail);
					$i++;
				}
			}
		}
		else {
			$this->returnError("Sorry, {$this->url} doesn't seem to be a Wordpress blog.", 404);
		}
	}

	private function getDetails($uri, $thumbnail) {
		$html = file_get_html($uri) or exit;
		$article = $html->find('.post', 0);

		$title = $article->find('h1', 0)->innertext;
		if (strlen($title) == 0)
			$title = $article->find('h2', 0)->innertext;

		$item = new \Item();
		$item->uri = $uri;
		$item->title = htmlspecialchars_decode($title);
		$item->author = $article->find('a[rel=author]', 0)->innertext;
		$item->thumbnailUri = $thumbnail;
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

