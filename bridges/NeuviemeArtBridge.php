<?php
class NeuviemeArtBridge extends FeedExpander {

	const MAINTAINER = 'ORelio';
	const NAME = '9ème Art Bridge';
	const URI = 'http://www.9emeart.fr/';
	const DESCRIPTION = 'Returns the newest articles.';

	protected function parseItem($item){
		$item = parent::parseItem($item);

		$article_html = getSimpleHTMLDOMCached($item['uri']);
		if(!$article_html) {
			$item['content'] = 'Could not request 9eme Art: ' . $item['uri'];
			return $item;
		}

		$article_image = '';
		foreach ($article_html->find('img.img_full') as $img) {
			if ($img->alt == $item['title']) {
				$article_image = self::URI . $img->src;
				break;
			}
		}

		$article_content = '';
		if ($article_image) {
			$article_content = '<p><img src="' . $article_image . '" /></p>';
		}
		$article_content .= str_replace(
			'src="/', 'src="' . self::URI,
			$article_html->find('div.newsGenerique_con', 0)->innertext
		);
		$article_content = stripWithDelimiters($article_content, '<script', '</script>');
		$article_content = stripWithDelimiters($article_content, '<style', '</style>');
		$article_content = stripWithDelimiters($article_content, '<link', '>');

		$item['content'] = $article_content;

		return $item;
	}

	public function collectData(){
		$feedUrl = self::URI . '9emeart.rss';
		$this->collectExpandableDatas($feedUrl);
	}
}
