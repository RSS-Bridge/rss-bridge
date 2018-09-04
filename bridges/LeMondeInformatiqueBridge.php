<?php
class LeMondeInformatiqueBridge extends FeedExpander {

	const MAINTAINER = 'ORelio';
	const NAME = 'Le Monde Informatique';
	const URI = 'http://www.lemondeinformatique.fr/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns the newest articles.';

	public function collectData(){
		$this->collectExpandableDatas(self::URI . 'rss/rss.xml', 10);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		$article_html = getSimpleHTMLDOMCached($item['uri'])
			or returnServerError('Could not request LeMondeInformatique: ' . $item['uri']);
		$item['content'] = $this->cleanArticle($article_html->find('div#article', 0)->innertext);
		$item['title'] = $article_html->find('h1.cleanprint-title', 0)->plaintext;
		return $item;
	}

	private function stripCDATA($string){
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>', '', $string);
		return $string;
	}


		return $string;
	}

	private function cleanArticle($article_html){
		$article_html = $this->stripWithDelimiters($article_html, '<h1 class="cleanprint-title"', '</h1>');
		$article_html = stripWithDelimiters($article_html, '<script', '</script>');
		return $article_html;
	}
}
