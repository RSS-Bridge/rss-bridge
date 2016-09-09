<?php
define('WORDPRESS_TYPE_ATOM', 1); // Content is of type ATOM
define('WORDPRESS_TYPE_RSS', 2); // Content is of type RSS
class WordPressBridge extends HttpCachingBridgeAbstract {

	public $sitename; // Name of the site

	const MAINTAINER = "aledeg";
	const NAME = "Wordpress Bridge";
	const URI = "https://wordpress.org/";
	const DESCRIPTION = "Returns the 3 newest full posts of a Wordpress blog";

	const PARAMETERS = array( array(
		'url'=>array(
			'name'=>'Blog URL',
			'required'=>true
		)
	));

	// Replaces all 'link' tags with 'url' for simplehtmldom to actually find 'links' ('url')
	private function ReplaceLinkTagsWithUrlTags($element){
		// We need to fix the 'link' tag as simplehtmldom cannot parse it (just rename it and load back as dom)
		$element_text = $element->outertext;
		$element_text = str_replace('<link>', '<url>', $element_text);
		$element_text = str_replace('</link>', '</url>', $element_text);
		$element_text = str_replace('<link ', '<url ', $element_text);
		return str_get_html($element_text);
	}

	private function StripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}

	private function ClearContent($content) {
		$content = preg_replace('/<script[^>]*>[^<]*<\/script>/', '', $content);
		$content = preg_replace('/<div class="wpa".*/', '', $content);
		$content = preg_replace('/<form.*\/form>/', '', $content);
		return $content;
	}

	public function collectData(){

		$html = $this->getSimpleHTMLDOM($this->getURI().'/feed/atom')
				or $this->returnServerError("Could not request ".$this->getURI().'/feed/atom');

		// Notice: We requested an ATOM feed, however some sites return RSS feeds instead!
		if($html->find('entry')){
				$type=WORDPRESS_TYPE_ATOM;
		}else if($html->find('item')){
				$type=WORDPRESS_TYPE_RSS;
		}else{
				$type=WORDPRESS_TYPE_ATOM; // Make ATOM default
		}

		if($type === WORDPRESS_TYPE_RSS)
			$posts = $html->find('item');
		else
			$posts = $html->find('entry');

		if(!empty($posts) ) {
			$this->sitename = $html->find('title', 0)->plaintext;
			$i=0;

			foreach ($posts as $article) {
				if($i < 3) {

					$item = array();

					$article = $this->ReplaceLinkTagsWithUrlTags($article);

					if($type === WORDPRESS_TYPE_RSS){
						$item['uri'] = $article->find('url', 0)->innertext; // 'link' => 'url'!
						$item['title'] = $article->find('title', 0)->plaintext;
						$item['author'] = trim($this->StripCDATA($article->find('dc:creator', 0)->innertext));
						$item['timestamp'] = strtotime($article->find('pubDate', 0)->innertext);
					} else {
						$item['uri'] = $article->find('url', 0)->getAttribute('href'); // 'link' => 'url'!
						$item['title'] = $this->StripCDATA($article->find('title', 0)->plaintext);
						$item['author'] = trim($article->find('author', 0)->innertext);
						$item['timestamp'] = strtotime($article->find('updated', 0)->innertext);
					}

					$article_html = $this->get_cached($item['uri']);

					// Attempt to find most common content div
					if(!isset($item['content'])){
						$article = $article_html->find('article', 0);
						if(!empty($article)){
							$item['content'] = $this->ClearContent($article->innertext);
						}
					}

					// another common content div
					if(!isset($item['content'])){
						$article = $article_html->find('.single-content', 0);
						if(!empty($article)){
							$item['content'] = $this->ClearContent($article->innertext);
						}
					}

					// for old WordPress themes without HTML5
					if(!isset($item['content'])){
						$article = $article_html->find('.post', 0);
						if(!empty($article)){
							$item['content'] = $this->ClearContent($article->innertext);
						}
					}

					$this->items[] = $item;
					$i++;
				}
			}
		} else {
			$this->returnServerError("Sorry, ".$this->getURI()." doesn't seem to be a Wordpress blog.");
		}
	}

	public function getURI(){
		return $this->getInput('url');
	}

	public function getName() {
		return "{$this->sitename} - Wordpress Bridge";
	}

	public function getCacheDuration() {
		return 3600*3; // 3 hours
	}
}
