<?php
class WordPressBridge extends FeedExpander {
	const MAINTAINER = "aledeg";
	const NAME = "Wordpress Bridge";
	const URI = "https://wordpress.org/";
	const CACHE_TIMEOUT = 10800; // 3h
	const DESCRIPTION = "Returns the newest full posts of a Wordpress powered website";

	const PARAMETERS = array( array(
		'url'=>array(
			'name'=>'Blog URL',
			'required'=>true
		)
	));

	private function clearContent($content) {
		$content = preg_replace('/<script[^>]*>[^<]*<\/script>/', '', $content);
		$content = preg_replace('/<div class="wpa".*/', '', $content);
		$content = preg_replace('/<form.*\/form>/', '', $content);
		return $content;
	}

	protected function parseItem($newItem){
		$item=parent::parseItem($newItem);

		$article_html = getSimpleHTMLDOMCached($item['uri']);

		$article=null;
		switch(true){
		case !is_null($article_html->find('article',0)):
			// most common content div
			$article = $article_html->find('article', 0);
			break;
		case !is_null($article_html->find('.single-content',0)):
			// another common content div
			$article = $article_html->find('.single-content', 0);
			break;
		case !is_null($article_html->find('.post-content',0)):
			// another common content div
			$article = $article_html->find('.post-content', 0);
			break;

		case !is_null($article_html->find('.post',0)):
			// for old WordPress themes without HTML5
			$article = $article_html->find('.post', 0);
			break;
		}

		if(!is_null($article)){
			$item['content'] = $this->clearContent($article->innertext);
		}

		return $item;
	}

	public function getURI(){
		$url = $this->getInput('url');
		if(empty($url)){
			$url = static::URI;
		}
		return $url;
	}

	public function collectData(){
		if($this->getInput('url') && substr($this->getInput('url'),0,strlen('http'))!=='http'){
			// just in case someone find a way to access local files by playing with the url
			returnClientError('The url parameter must either refer to http or https protocol.');
		}

		$this->collectExpandableDatas($this->getURI().'/feed/atom/');

	}
}
