<?php
class ReutersBridge extends BridgeAbstract
{
	const MAINTAINER = 'hollowleviathan, spraynard, csisoap';
	const NAME = 'Reuters Bridge';
	const URI = 'https://reuters.com/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns news from Reuters';

	private $feedName = self::NAME;

	/**
	 * Wireitem types allowed in the final story output
	 */
	const ALLOWED_WIREITEM_TYPES = array(
		'story',
		'headlines'
	);

	/**
	 * Wireitem template types allowed in the final story output
	 */
	const ALLOWED_TEMPLATE_TYPES = array(
		'story'
	);

	const FEED_REGION_VALUE_US = 'us';
	const FEED_REGION_VALUE_UK = 'uk';

	const PARAMETERS = array(
		'United Kingdom' => array(
			'feed_uk' => array(
				'name' => 'News Feed',
				'type' => 'list',
				'title' => 'Feeds from Reuters U.K edition',
				'values' => array(
					'Tech' => 'tech',
					'Wire' => 'wire',
					'Business' => 'business',
					'World' => 'world',
					'Politics' => 'politics',
					'Science' => 'science',
					'Energy' => 'energy',
					'Aerospace and Defense' => 'aerospace',
					'Special Reports' => 'special-reports',
					'Top News' => 'home/topnews',
					'Markets' => 'markets',
					'Sports' => 'sports',
					'UK' => 'uk',
					'Entertainment' => 'entertainment',
					'Environment' => 'environment',
					'Lifestyle' => 'lifestyle',
				)
			)
		),
		'United States' => array(
			'feed_us' => array(
				'name' => 'News Feed',
				'type' => 'list',
				'title' => 'Feeds from Reuters U.S/International edition',
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
					'Aerospace and Defense' => 'aerospace',
					'Special Reports' => 'special-reports',
					'China' => 'china',
					'Top News' => 'home/topnews',
					'Markets' => 'markets',
					'Sports' => 'sports',
					'USA News' => 'us',
				)
			)
		)
	);

	/**
	 * Performs an HTTP request to the Reuters API and returns decoded JSON
	 * in the form of an associative array
	 * @param string $feed_uri Parameter string to the Reuters API
	 * @return array
	 */
	private function getJson($feed_uri)
	{
		$uri = "https://wireapi.reuters.com/v8$feed_uri";
		$returned_data = getContents($uri);
		return json_decode($returned_data, true);
	}

	/**
	 * Takes in data from Reuters Wire API and
	 * creates structured data in the form of a list
	 * of story information.
	 * @param array $data JSON collected from the Reuters Wire API
	 */
	private function processData($data)
	{
		/**
		 * Gets a list of wire items which are groups of templates
		 */
		$reuters_allowed_wireitems = array_filter(
			$data, function ($wireitem) {
				return in_array(
					$wireitem['wireitem_type'],
					self::ALLOWED_WIREITEM_TYPES
				);
			}
		);

		/*
		* Gets a list of "Templates", which is data containing a story
		*/
		$reuters_wireitem_templates = array_reduce(
			$reuters_allowed_wireitems,
			function (array $carry, array $wireitem) {
				$wireitem_templates = $wireitem['templates'];
				return array_merge(
					$carry,
					array_filter(
						$wireitem_templates, function (
							array $template_data
						) {
							return in_array(
								$template_data['type'],
								self::ALLOWED_TEMPLATE_TYPES
							);
						}
					)
				);
			},
			array()
		);

		return $reuters_wireitem_templates;
	}

	private function getArticle($feed_uri)
	{
		// This will make another request to API to get full detail of article and author's name.
		$rawData = $this->getJson($feed_uri);
		$reuters_wireitems = $rawData['wireitems'];
		$processedData = $this->processData($reuters_wireitems);

		$first = reset($processedData);
		$article_content = $first['story']['body_items'];
		$authorlist = $first['story']['authors'];
		$category = $first['story']['channel']['name'];
		$image_list = $first['story']['images'];
		$img_placeholder = '';

		foreach($image_list as $image) { // Add more image to article.
			$image_url = $image['url'];
			$image_caption = $image['caption'];
			$img = "<img src=\"$image_url\">";
			$img_caption = "<figcaption style=\"text-align: center;\"><i>$image_caption</i></figcaption>";
			$figure = "<figure>$img \t $img_caption</figure>";
			$img_placeholder = $img_placeholder . $figure;
		}

		$author = '';
		$counter = 0;
		foreach ($authorlist as $data) {
			//Formatting author's name.
			$counter++;
			$name = $data['name'];
			if ($counter == count($authorlist)) {
				$author = $author . $name;
			} else {
				$author = $author . "$name, ";
			}
		}

		$description = '';
		foreach ($article_content as $content) {
			$data;
			if(isset($content['content'])) {
				$data = $content['content'];
			}
			switch($content['type']) {
				case 'paragraph':
					$description = $description . "<p>$data</p>";
					break;
				case 'heading':
					$description = $description . "<h3>$data</h3>";
					break;
				case 'infographics':
					$description = $description . "<img src=\"$data\">";
					break;
				case 'inline_items':
					$item_list = $content['items'];
					$description = $description . '<p>';
					foreach ($item_list as $item) {
						if($item['type'] == 'text') {
							$description = $description . $item['content'];
						} else {
							$description = $description . $item['symbol'];
						}
					}
					$description = $description . '</p>';
					break;
				case 'p_table':
					$description = $description . $content['content'];
					break;
			}
		}

		$content_detail = array(
			'content' => $description,
			'author' => $author,
			'category' => $category,
			'images' => $img_placeholder,
		);
		return $content_detail;
	}

	public function getName() {
		return $this->feedName;
	}

	public function collectData()
	{
		$reuters_feed_name = null;
		$reuters_feed_region = null;

		switch ($this->queriedContext) {
			case 'United Kingdom':
				$reuters_feed_name = $this->getInput('feed_uk');
				$reuters_feed_region = self::FEED_REGION_VALUE_UK;
				break;
			case 'United States':
				$reuters_feed_name = $this->getInput('feed_us');
				$reuters_feed_region = self::FEED_REGION_VALUE_US;
				break;
		}

		$feed_uri = "/feed/rapp/$reuters_feed_region/tabbar/feeds/$reuters_feed_name";

		$data = $this->getJson($feed_uri);
		$reuters_wireitems = $data['wireitems'];
		$this->feedName = $data['wire_name'] . ' | Reuters';
		$processedData = $this->processData($reuters_wireitems);

		// Merge all articles from Editor's Highlight section into existing array of templates.
		$top_section = reset($reuters_wireitems);
		if ($top_section['wireitem_type'] == 'headlines') {
			$top_articles = $top_section['templates'][1]['headlines'];
			$processedData = array_merge($top_articles, $processedData);
		}

		foreach ($processedData as $story) {
			$item['uid'] = $story['story']['usn'];
			$article_uri = $story['template_action']['api_path'];
			$content_detail = $this->getArticle($article_uri);
			$description = $content_detail['content'];
			$author = $content_detail['author'];
			$images = $content_detail['images'];
			$item['categories'] = array($content_detail['category']);
			$item['author'] = $author;
			if (!(bool) $description) {
				$description = $story['story']['lede']; // Just in case the content doesn't have anything.
			} else {
				$item['content'] = "$description  $images";
			}

			$item['title'] = $story['story']['hed'];
			$item['timestamp'] = $story['story']['updated_at'];
			$item['uri'] = $story['template_action']['url'];
			$this->items[] = $item;
		}
	}
}
