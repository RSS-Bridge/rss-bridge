<?php
class NextgovBridge extends FeedExpander {

	const MAINTAINER = 'ORelio';
	const NAME = 'Nextgov Bridge';
	const URI = 'https://www.nextgov.com/';
	const DESCRIPTION = 'USA Federal technology news, best practices, and web 2.0 tools.';

	const PARAMETERS = array( array(
		'category' => array(
			'name' => 'Category',
			'type' => 'list',
			'values' => array(
				'All' => 'all',
				'Technology News' => 'technology-news',
				'CIO Briefing' => 'cio-briefing',
				'Emerging Tech' => 'emerging-tech',
				'Cloud' => 'cloud-computing',
				'Cybersecurity' => 'cybersecurity',
				'Mobile' => 'mobile',
				'Health' => 'health',
				'Defense' => 'defense',
				'Big Data' => 'big-data'
			)
		)
	));

	public function collectData(){
		$this->collectExpandableDatas(self::URI . 'rss/' . $this->getInput('category') . '/', 10);
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);

		$item['content'] = '';

		$namespaces = $newsItem->getNamespaces(true);
		if(isset($namespaces['media'])) {
			$media = $newsItem->children($namespaces['media']);
			if(isset($media->content)) {
				$attributes = $media->content->attributes();
				$item['content'] = '<img src="' . $attributes['url'] . '">';
			}
		}

		$item['content'] .= $this->extractContent($item['uri']);
		return $item;
	}

	private function extractContent($url){
		$article = getSimpleHTMLDOMCached($url)
			or returnServerError('Could not request Nextgov: ' . $url);

		$contents = $article->find('div.wysiwyg', 0)->innertext;
		$contents = ($article_thumbnail == '' ? '' : '<p><img src="' . $article_thumbnail . '" /></p>')
			. '<p><b>'
			. $article_subtitle
			. '</b></p>'
			. trim($contents);
		$contents = stripWithDelimiters($contents, '<div class="ad-container">', '</div>');
		$contents = stripWithDelimiters($contents, '<div', '</div>'); //ad outer div
		return trim(stripWithDelimiters($contents, '<script', '</script>'));
	}
}
