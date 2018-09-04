<?php
class TheHackerNewsBridge extends BridgeAbstract {

	const MAINTAINER = 'ORelio';
	const NAME = 'The Hacker News Bridge';
	const URI = 'https://thehackernews.com/';
	const DESCRIPTION = 'Cyber Security, Hacking, Technology News.';

	public function collectData(){

		$html = getSimpleHTMLDOM($this->getURI())
			or returnServerError('Could not request TheHackerNews: ' . $this->getURI());
		$limit = 0;

		foreach($html->find('article') as $element) {
			if($limit < 5) {

				$article_url = $element->find('a.entry-title', 0)->href;
				$article_author = trim($element->find('span.vcard', 0)->plaintext);
				$article_title = $element->find('a.entry-title', 0)->plaintext;
				$article_timestamp = strtotime($element->find('span.updated', 0)->plaintext);
				$article = getSimpleHTMLDOM($article_url)
					or returnServerError('Could not request TheHackerNews: ' . $article_url);

				$contents = $article->find('div.articlebodyonly', 0)->innertext;
				$contents = stripRecursiveHtmlSection($contents, 'div', '<div class=\'clear\'');
				$contents = stripWithDelimiters($contents, '<script', '</script>');

				$item = array();
				$item['uri'] = $article_url;
				$item['title'] = $article_title;
				$item['author'] = $article_author;
				$item['timestamp'] = $article_timestamp;
				$item['content'] = trim($contents);
				$this->items[] = $item;
				$limit++;
			}
		}

	}
}
