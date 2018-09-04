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

		$article_thumbnail = 'https://cdn.nextgov.com/nextgov/images/logo.png';
		$item['content'] = '<p><b>' . $item['content'] . '</b></p>';

		$namespaces = $newsItem->getNamespaces(true);
		if(isset($namespaces['media'])) {
			$media = $newsItem->children($namespaces['media']);
			if(isset($media->content)) {
				$attributes = $media->content->attributes();
				$item['content'] = '<p><img src="' . $attributes['url'] . '"></p>' . $item['content'];
				$article_thumbnail = str_replace(
					'large.jpg',
					'small.jpg',
					strval($attributes['url'])
				);
			}
		}

		$item['enclosures'] = array($article_thumbnail);
		$item['content'] .= $this->extractContent($item['uri']);
		return $item;
	}

	private function extractContent($url){
		$article = getSimpleHTMLDOMCached($url);

		if (!is_object($article))
			return 'Could not request Nextgov: ' . $url;

		$contents = $article->find('div.wysiwyg', 0);
		$contents->find('svg.content-tombstone', 0)->outertext = '';
		$contents = $contents->innertext;
		$contents = stripWithDelimiters($contents, '<div class="ad-container">', '</div>');
		$contents = stripWithDelimiters($contents, '<div', '</div>'); //ad outer div
		return trim(stripWithDelimiters($contents, '<script', '</script>'));
	}
}
