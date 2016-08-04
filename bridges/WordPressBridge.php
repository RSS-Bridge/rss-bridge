<?php
define('WORDPRESS_TYPE_ATOM', 1); // Content is of type ATOM
define('WORDPRESS_TYPE_RSS', 2); // Content is of type RSS
class WordPressBridge extends BridgeAbstract {

	private $url;

	public function loadMetadatas() {

		$this->maintainer = "aledeg";
		$this->name = "Wordpress Bridge";
		$this->uri = "https://wordpress.org/";
		$this->description = "Returns the 3 newest full posts of a Wordpress blog";
		$this->update = "2016-08-04";

		$this->parameters[] =
		'[
			{
				"name" : "blog URL",
				"required" : "true",
				"identifier" : "url"
			}
		]';

	}

	// Returns the content type for a given html dom
	function DetectContentType($html){
		if($html->find('entry'))
			return WORDPRESS_TYPE_ATOM;
		if($html->find('item'))
			return WORDPRESS_TYPE_RSS;
		return WORDPRESS_TYPE_ATOM; // Make ATOM default
	}

	// Replaces all 'link' tags with 'url' for simplehtmldom to actually find 'links' ('url')	
	function ReplaceLinkTagsWithUrlTags($element){
		// We need to fix the 'link' tag as simplehtmldom cannot parse it (just rename it and load back as dom)
		$element_text = $element->outertext;
		$element_text = str_replace('<link>', '<url>', $element_text);
		$element_text = str_replace('</link>', '</url>', $element_text);
		$element_text = str_replace('<link ', '<url ', $element_text);
		return str_get_html($element_text);
	}

	function StripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}

	function ClearContent($content) {
		$content = preg_replace('/<script.*\/script>/', '', $content);
		$content = preg_replace('/<div class="wpa".*/', '', $content);
		return $content;
	}

	public function collectData(array $param) {
		$this->processParams($param);

		if (!$this->hasUrl()) {
			$this->returnError('You must specify a URL', 400);
		}

                $this->url = $this->url.'/feed/atom';
		$html = $this->file_get_html($this->url) or $this->returnError("Could not request {$this->url}.", 404);

		// Notice: We requested an ATOM feed, however some sites return RSS feeds instead!
		$type = $this->DetectContentType($html);

		if($type === WORDPRESS_TYPE_RSS)
			$posts = $html->find('item');
		else
			$posts = $html->find('entry');


                if(!empty($posts) ) {
                        $this->name = $html->find('title', 0)->plaintext;
                        $i=0;
			foreach ($posts as $article) {
				if($i < 3) {

					$item = new \Item();

					$article = $this->ReplaceLinkTagsWithUrlTags($article);

					if($type === WORDPRESS_TYPE_RSS){
						$item->uri = $article->find('url', 0)->innertext; // 'link' => 'url'!
						$item->title = $article->find('title', 0)->plaintext;
						$item->author = trim($article->find('dc:creator', 0)->innertext);
						$item->timestamp = strtotime($article->find('pubDate', 0)->innertext);
					} else {
						$item->uri = $article->find('url', 0)->getAttribute('href'); // 'link' => 'url'!
						$item->title = $this->StripCDATA($article->find('title', 0)->plaintext);
						$item->author = trim($article->find('author', 0)->innertext);
						$item->timestamp = strtotime($article->find('updated', 0)->innertext);
					}

                                        $article_html = $this->file_get_html($item->uri);

					$article = $article_html->find('article', 0);
					if(!empty($article)){
						$item->content = $this->ClearContent($article->innertext);
						if(empty($item->content))
							$item->content = $this->ClearContent($article_html->find('.single-content', 0)->innertext); // another common content div
						if(empty($item->content))
							$item->content = $this->ClearContent($article_html->find('.post', 0)->innertext); // for old WordPress themes without HTML5
					}
					$this->items[] = $item;
					$i++;
				}
			} 
                }
		else {
			$this->returnError("Sorry, {$this->url} doesn't seem to be a Wordpress blog.", 404);
		}
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
	}

}

