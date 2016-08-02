<?php
class WordPressBridge extends BridgeAbstract {

	private $url;

	public function loadMetadatas() {

		$this->maintainer = "aledeg";
		$this->name = "Wordpress Bridge";
		$this->uri = "https://wordpress.org/";
		$this->description = "Returns the 3 newest full posts of a Wordpress blog";
		$this->update = "2016-08-02";

		$this->parameters[] =
		'[
			{
				"name" : "blog URL",
				"required" : "true",
				"identifier" : "url"
			}
		]';

	}

	public function collectData(array $param) {

                function StripCDATA($string) {
                        $string = str_replace('<![CDATA[', '', $string);
                        $string = str_replace(']]>', '', $string);
                        return $string;
                }

                function clearContent($content) {
                        $content = preg_replace('/<script.*\/script>/', '', $content);
                        $content = preg_replace('/<div class="wpa".*/', '', $content);
                        return $content;
                }

		$this->processParams($param);

		if (!$this->hasUrl()) {
			$this->returnError('You must specify a URL', 400);
		}

                $this->url = $this->url.'/feed/atom';
		$html = $this->file_get_html($this->url) or $this->returnError("Could not request {$this->url}.", 404);
		$posts = $html->find('entry');
                if(!empty($posts) ) {
                        $this->name = $html->find('title', 0)->plaintext;
                        $i=0;
			foreach ($html->find('entry') as $article) {
				if($i < 3) {
					$this->items[$i]->uri = $article->find('link', 0)->getAttribute('href');
					$this->items[$i]->title = StripCDATA($article->find('title', 0)->plaintext);
					$this->items[$i]->author = trim($article->find('author', 0)->innertext);
					$this->items[$i]->timestamp = strtotime($article->find('updated', 0)->innertext);

                                        $article_html = $this->file_get_html($this->items[$i]->uri);
					$this->items[$i]->content = clearContent($article_html->find('article', 0)->innertext);
                                        if(empty($this->items[$i]->content))
					        $this->items[$i]->content = clearContent($article_html->find('.post', 0)->innertext); // for old WordPress themes without HTML5

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

