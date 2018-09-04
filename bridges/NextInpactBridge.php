<?php
class NextInpactBridge extends FeedExpander {

	const MAINTAINER = 'qwertygc';
	const NAME = 'NextInpact Bridge';
	const URI = 'https://www.nextinpact.com/';
	const DESCRIPTION = 'Returns the newest articles.';

	const PARAMETERS = array( array(
		'feed' => array(
			'name' => 'Feed',
			'type' => 'list',
			'values' => array(
				'Tous nos articles' => 'news',
				'Nos contenus en accès libre' => 'acces-libre',
				'Blog' => 'blog',
				'Bons plans' => 'bonsplans'
			)
		),
		'filter_premium' => array(
			'name' => 'Premium',
			'type' => 'list',
			'values' => array(
				'No filter' => '0',
				'Hide Premium' => '1',
				'Only Premium' => '2'
			)
		),
		'filter_brief' => array(
			'name' => 'Brief',
			'type' => 'list',
			'values' => array(
				'No filter' => '0',
				'Hide Brief' => '1',
				'Only Brief' => '2'
			)
		)
	));

	public function collectData(){
		$feed = $this->getInput('feed');
		if (empty($feed))
			$feed = 'news';
		$this->collectExpandableDatas(self::URI . 'rss/' . $feed . '.xml');
	}

	protected function parseItem($newsItem){
		$item = parent::parseItem($newsItem);
		$item['content'] = $this->extractContent($item, $item['uri']);
		if (is_null($item['content']))
			return null; //Filtered article
		return $item;
	}

	private function extractContent($item, $url){
		$html = getSimpleHTMLDOMCached($url);
		if (!is_object($html))
			return 'Failed to request NextInpact: ' . $url;

		foreach(array(
			'filter_premium' => 'h2.title_reserve_article',
			'filter_brief' => 'div.brief-inner-content'
		) as $param_name => $selector) {
			$param_val = intval($this->getInput($param_name));
			if ($param_val != 0) {
				$element_present = is_object($html->find($selector, 0));
				$element_wanted = ($param_val == 2);
				if ($element_present != $element_wanted) {
					return null; //Filter article
				}
			}
		}

		if (is_object($html->find('div[itemprop=articleBody], div.brief-inner-content', 0))) {

			$subtitle = trim($html->find('span.sub_title, div.brief-head', 0));
			if(is_object($subtitle) && $subtitle->plaintext !== $item['title']) {
				$subtitle = '<p><em>' . $subtitle->plaintext . '</em></p>';
			} else {
				$subtitle = '';
			}

			$postimg = $html->find(
				'div.container_main_image_article, div.image-brief-container, div.image-brief-side-container', 0
			);
			if(is_object($postimg)) {
				$postimg = '<p><img src="'
				. $postimg->find('img.dedicated', 0)->src
				. '" alt="-" /></p>';
			} else {
				$postimg = '';
			}

			$text = $subtitle
				. $postimg
				. $html->find('div[itemprop=articleBody], div.brief-inner-content', 0)->outertext;

		} else {
			$text = $item['content']
				. '<p><em>Failed retrieve full article content</em></p>';
		}

		$premium_article = $html->find('h2.title_reserve_article', 0);
		if (is_object($premium_article)) {
			$text .= '<p><em>' . $premium_article->innertext . '</em></p>';
		}

		return $text;
	}
}
