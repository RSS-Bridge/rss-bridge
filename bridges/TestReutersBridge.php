<?php
class ReutersBridge extends BridgeAbstract {

	const MAINTAINER = 'hollowleviathan, spraynard, csisoap';
	const NAME = 'Test Reuters Bridge';
	const URI = 'https://reuters.com/';
	const CACHE_TIMEOUT = 120; // 30min
	const DESCRIPTION = 'Returns news from Reuters';
	private $feedName = self::NAME;
	private $feedDesc = self::DESCRIPTION;

	const ALLOWED_WIREITEM_TYPES = array(
		'story',
		'headlines'
	);

	const ALLOWED_TEMPLATE_TYPES = array(
		'story'
	);

	const PARAMETERS = array(array(
		'feed' => array(
			'name'	=> 'News Feed',
			'type' => 'list',
			'exampleValue'	=> 'World',
			'title' => 'Reuters feed. World, US, Tech...',
			'values' => array(
				'Tech' => 'tech',
				'Wire' => 'wire',
				'Health' => 'health',
				'Business' => 'business',
				'World' => 'world',
				'Politics' => 'politics',
				'Science' => 'science',
				'Lifestyle' => 'life',
				'Energy' => 'energy',
				'Aerospace and Defence' => 'aerospace',
				'Markets' => 'markets',
				'Sports' => 'sports',
				'Pic of the Day' => 'pictures',
				'USA News' => 'us',
				'China' => 'china',
				'Top News' => 'home/topnews',
			)
		),
	));

	private function getJson($feedname) {
		$uri = "https://wireapi.reuters.com/v8/feed/rapp/us/tabbar/feeds/$feedname";
		$returned_data = getContents($uri);
		return json_decode($returned_data, true);
	}

	
	public function getName() {
		return $this->feedName;
	}

	public function getDescription() { //This won't work.
		return $this->feedDesc;
	}

	public function collectData() {
		$feed = $this->getInput('feed');
		$data = $this->getJson($feed);
		$reuters_wireitems = $data['wireitems'];
		$this->feedName = $data['wire_name'] . ' | Reuters';
		$this->feedDesc = $data['wire_description'];
		/**
		 * Gets a list of wire items which are groups of templates
		 */

		$reuters_allowed_wireitems = array_filter($reuters_wireitems, function($wireitem) {
			return in_array($wireitem['wireitem_type'], self::ALLOWED_WIREITEM_TYPES);
		});

		/**
		 * Gets a list of "Templates", which is data containing a story
		 */
		$reuters_wireitem_templates = array_reduce($reuters_allowed_wireitems, function (array $carry, array $wireitem) {
			$wireitem_templates = $wireitem['templates'];
			return array_merge($carry, array_filter($wireitem_templates, function(array $template_data) {
				// Merge all articles from Editor's Highlight section into existing array of templates.
				$processed_data = array_filter($template_data, function($wireitem) {
					return in_array($wireitem['type'], 'headlines');
				});
				$template_data = array_merge($processed_data['headlines'], $template_data)
				
				return in_array($template_data['type'], self::ALLOWED_TEMPLATE_TYPES);
			}));
		}, array());

		// Merge all articles from Editor's Highlight section into existing array of templates.
		// $top_newslist = reset($reuters_allowed_wireitems);
		// if($top_newslist['wireitem_type'] == 'headlines') {
		// 	$top_articles = $top_newslist['templates'][1]['headlines'];

		// 	$reuters_wireitem_templates = array_merge($top_articles, $reuters_wireitem_templates);
		// }

		foreach ($reuters_wireitem_templates as $story) {
			$item['uid'] = $story['story']['usn'];
			$description = $story['story']['lede'];
			$image_url = $story['image']['url'];
			if(!(bool)$image_url) {
				$image_url = 'https://s4.reutersmedia.net/resources_v2/images/rcom-default.png';
			}
			$item['content'] = "$description \n
					   <img src=\"$image_url\">";
			$item['title'] = $story['story']['hed'];
			$item['timestamp'] = $story['story']['updated_at'];
			$item['uri'] = $story['template_action']['url'];

			$this->items[] = $item;
		}
	}
}
