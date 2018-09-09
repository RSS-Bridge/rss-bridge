<?php
class LeMondeInformatiqueBridge extends FeedExpander {

	const MAINTAINER = 'ORelio';
	const NAME = 'Le Monde Informatique';
	const URI = 'https://www.lemondeinformatique.fr/';
	const DESCRIPTION = 'Returns the newest articles.';

	public function collectData(){
		$this->collectExpandableDatas(self::URI . 'rss/rss.xml', 10);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		$article_html = getSimpleHTMLDOMCached($item['uri'])
			or returnServerError('Could not request LeMondeInformatique: ' . $item['uri']);

		//Deduce thumbnail URL from article image URL
		$item['enclosures'] = array(
			str_replace(
				'/grande/',
				'/petite/',
				$article_html->find('.article-image', 0)->find('img', 0)->src
			)
		);

		//No response header sets the encoding, explicit conversion is needed or subsequent xml_encode() will fail
		$item['content'] = utf8_encode($this->cleanArticle($article_html->find('div.col-primary', 0)->innertext));
		$item['author'] = utf8_encode($article_html->find('div.author-infos', 0)->find('b', 0)->plaintext);

		return $item;
	}

	private function cleanArticle($article_html){
		$article_html = stripWithDelimiters($article_html, '<script', '</script>');
		$article_html = explode('<p class="contact-error', $article_html)[0] . '</div>';
		return $article_html;
	}
}
