<?php
class ReutersBridge extends BridgeAbstract {

	const MAINTAINER = 'hollowleviathan, spraynard, csisoap';
	const NAME = 'Reuters Bridge';
	const URI = 'https://reuters.com/';
	const CACHE_TIMEOUT = 120; // 30min
	const DESCRIPTION = 'Returns news from Reuters';
	private $feedName = self::NAME;

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
	
	private function processData($data) {
		/**
		 * Gets a list of wire items which are groups of templates
		 */
		$reuters_allowed_wireitems = array_filter($data, function($wireitem) {
			return in_array($wireitem['wireitem_type'], self::ALLOWED_WIREITEM_TYPES);
		});

		/*
		 * Gets a list of "Templates", which is data containing a story
		 */
		$reuters_wireitem_templates = array_reduce($reuters_allowed_wireitems, function (array $carry, array $wireitem) {
			$wireitem_templates = $wireitem['templates'];
			return array_merge($carry, array_filter($wireitem_templates, function(array $template_data) {
				return in_array($template_data['type'], self::ALLOWED_TEMPLATE_TYPES);
			}));
		}, array());
		
		return $reuters_wireitem_templates;
	}

	private function getArticle($feed_uri) {
		$uri = "https://wireapi.reuters.com/v8$feed_uri";
		$data = getContents($uri);
		$process_data = json_decode($data, true);
		$reuters_wireitems = $process_data['wireitems'];
		$processedData = $this->processData($reuters_wireitems);
		
		$first = reset($processedData);
		$article_content = $first['story']['body_items'];
		$authorlist = $first['story']['authors'];
		
		$author = '';
		foreach($authorlist as $data) {
			$name = $data['name'];
			$author = $author . "$name, ";
		}
		
		$description = '';
		foreach($article_content as $content) {
			$data = $content['content'];
			$description = $description . "<p>$data</p>";
		}
		
		$content_detail = array(
			'content' => $description,
			'author' => $author
		);
		return $content_detail;
	}


	public function collectData() {
		$feed = $this->getInput('feed');
		$data = $this->getJson($feed);
		$reuters_wireitems = $data['wireitems'];
		$this->feedName = $data['wire_name'] . ' | Reuters';
		$processedData = $this->processData($reuters_wireitems);

		// Merge all articles from Editor's Highlight section into existing array of templates.
		$top_section = reset($processedData);
		if($top_section['wireitem_type'] == 'headlines') {
			$top_articles = $top_section['templates'][1]['headlines'];

			$reuters_wireitem_templates = array_merge($top_articles, $reuters_wireitem_templates);
		}

		foreach ($processedData as $story) {
			$item['uid'] = $story['story']['usn'];
			$article_uri = $story['template_action']['api_path'];
			$content_detail = $this->getArticle($article_uri);
			$description = $content_detail['content'];
			$author = $content_detail['author'];
			$item['author'] = $author;
			if(!(bool)$description) {
				$description = $story['story']['lede'];
			}
			#	$description = $story['story']['lede'];
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
