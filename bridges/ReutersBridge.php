<?php
class ReutersBridge extends BridgeAbstract {


	const MAINTAINER = 'hollowleviathan, spraynard, csisoap';
	const NAME = 'Reuters Bridge';
	const URI = 'https://reuters.com/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns news from Reuters';
	private $feedName = self::NAME;


	const ALLOWED_WIREITEM_TYPES = array(
		'story'
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
				'Energy' => 'energy',
				'Aerospace and Defence' => 'aerospace',
				'China' => 'china',
				'Top News' => 'home/topnews',
				'Lifestyle' => 'lifestyle',
				'Markets' => 'markets',
				'Sports' => 'sports',
				'Pic of the Day' => 'pictures', //This have different configuration than others.
				'USA News' => 'us'
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


	public function collectData() {
		$feed = $this->getInput('feed');
		$data = $this->getJson($feed);
		$reuters_wireitems = $data['wireitems'];
		$this->feedName = $data['wire_name'] . ' | Reuters';
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
				return in_array($template_data['type'], self::ALLOWED_TEMPLATE_TYPES);
			}));
		}, array());


		// Merge all articles from Editor's Highlight section into existing array of templates.
		$top_section = reset($reuters_allowed_wireitems);
                if($top_section['wireitem_type'] == 'headlines') {
                        $top_articles = $top_section['templates'][1]['headlines'];

                        $reuters_wireitem_templates = array_merge($top_articles, $reuters_wireitem_templates);
                }



		 foreach ($reuters_wireitem_templates as $story) {
                #       $item['uid'] = $story['story']['usn'];
                        $description = $story['story']['lede'];
                        $image_url = $story['image']['url'];
                        if(!(bool)$image_url) {
                                $image_url = 'https://s4.reutersmedia.net/resources_v2/images/rcom-default.png'; //In case some article doesn't include image.
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
