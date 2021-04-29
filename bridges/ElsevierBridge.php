<?php
class ElsevierBridge extends BridgeAbstract {

	const MAINTAINER = 'Pierre Mazière';
	const NAME = 'Elsevier journals recent articles';
	const URI = 'https://www.journals.elsevier.com/';
	const CACHE_TIMEOUT = 43200; //12h
	const DESCRIPTION = 'Returns the recent articles published in Elsevier journals';

	const PARAMETERS = array( array(
		'j' => array(
			'name' => 'Journal name',
			'required' => true,
			'exampleValue' => 'academic-pediactrics',
			'title' => 'Insert html-part of your journal'
		)
	));

	// Extracts the list of names from an article as string
	private function extractArticleName($article){
		$names = $article->find('small', 0);
		if($names)
			return trim($names->plaintext);
		return '';
	}

	// Extracts the timestamp from an article
	private function extractArticleTimestamp($article){
		$time = $article->find('.article-info', 0);
		if($time) {
			$timestring = trim($time->plaintext);
			/*
				The format depends on the age of an article:
				- Available online 29 July 2016
				- July 2016
				- May–June 2016
			*/
			if(preg_match('/\S*(\d+\s\S+\s\d{4})/ims', $timestring, $matches)) {
				return strtotime($matches[0]);
			} elseif (preg_match('/[A-Za-z]+\-([A-Za-z]+\s\d{4})/ims', $timestring, $matches)) {
				return strtotime($matches[0]);
			} elseif (preg_match('/([A-Za-z]+\s\d{4})/ims', $timestring, $matches)) {
				return strtotime($matches[0]);
			} else {
				return 0;
			}
		}
		return 0;
	}

	// Extracts the content from an article
	private function extractArticleContent($article){
		$content = $article->find('.article-content', 0);
		if($content) {
			return trim($content->plaintext);
		}
		return '';
	}

	public function getIcon() {
		return 'https://cdn.elsevier.io/verona/includes/favicons/favicon-32x32.png';
	}

	public function collectData(){
		$uri = self::URI . $this->getInput('j') . '/recent-articles/';
		$html = getSimpleHTMLDOM($uri)
			or returnServerError('No results for Elsevier journal ' . $this->getInput('j'));

		foreach($html->find('.pod-listing') as $article) {
			$item = array();
			$item['uri'] = $article->find('.pod-listing-header>a', 0)->getAttribute('href') . '?np=y';
			$item['title'] = $article->find('.pod-listing-header>a', 0)->plaintext;
			$item['author'] = $this->extractArticleName($article);
			$item['timestamp'] = $this->extractArticleTimestamp($article);
			$item['content'] = $this->extractArticleContent($article);
			$this->items[] = $item;
		}
	}
}
