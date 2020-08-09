<?php
class ReutersBridge extends BridgeAbstract
{
	const MAINTAINER = 'hollowleviathan, spraynard, csisoap';
	const NAME = 'Reuters Bridge';
	const URI = 'https://reuters.com/';
	const CACHE_TIMEOUT = 1800; // 30min
	const DESCRIPTION = 'Returns news from Reuters';
	private $feedName = self::NAME;

	const ALLOWED_WIREITEM_TYPES = array(
		'story',
		'headlines'
	);

	const ALLOWED_TEMPLATE_TYPES = array(
		'story'
	);

	const PARAMETERS = array(
		array(
			'feed' => array(
				'name' => 'News Feed',
				'type' => 'list',
				'exampleValue' => 'World',
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
					'China' => 'china',
					'Top News' => 'home/topnews',
					'Markets' => 'markets',
					'Sports' => 'sports',
					'Pic of the Day' => 'pictures', // This has a different configuration than the others.
					'USA News' => 'us',
				),
			),
		),
	);

	private function getJson($feedname)
	{
		$uri = "https://wireapi.reuters.com/v8/feed/rapp/us/tabbar/feeds/$feedname";
		$returned_data = getContents($uri);
		return json_decode($returned_data, true);
	}

	public function getName()
	{
		return $this->feedName;
	}

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
		$uri = "https://wireapi.reuters.com/v8$feed_uri";
		$data = getContents($uri);
		$process_data = json_decode($data, true);
		$reuters_wireitems = $process_data['wireitems'];
		$processedData = $this->processData($reuters_wireitems);

		$first = reset($processedData);
		$article_content = $first['story']['body_items'];
		$authorlist = $first['story']['authors'];

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
			$data = $content['content'];
			// This will check whether that content is a image URL or not.
			if (strpos($data, '.png') !== false
				|| strpos($data, '.jpg') !== false
				|| strpos($data, '.PNG') !== false
			) {
				$description = $description . "<img src=\"$data\">";
			} else {
				if ($content['type'] == 'inline_items') {
					//Fix issue with some content included brand name or company name.
					$item_list = $content['items'];
					$description = $description . '<p>';
					foreach ($item_list as $item) {
						$description = $description . $item['content'];
					}
					$description = $description . '</p>';
				} else {
					if (strtoupper($data) == $data
						|| $content['type'] == 'heading'
					) {
						//Add heading for any part of content served as header.
						$description = $description . "<h3>$data</h3>";
					} else {
						$description = $description . "<p>$data</p>";
					}
				}
			}
		}

		$content_detail = array(
			'content' => $description,
			'author' => $author,
		);
		return $content_detail;
	}

	public function collectData()
	{
		$feed = $this->getInput('feed');
		$data = $this->getJson($feed);
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
			$item['author'] = $author;
			if (!(bool) $description) {
				$description = $story['story']['lede']; // Just in case the content doesn't have anything.
			}
			// $description = $story['story']['lede'];
			$image_url = $story['image']['url'];
			if (!(bool) $image_url) {
				// $image_url =
				// 'https://s4.reutersmedia.net/resources_v2/images/rcom-default.png'; //Just in case if there aren't any pictures.
				$item['content'] = $description;
			} else {
				$item['content'] = "<img src=\"$image_url\"> \n
					$description";
			}
			$item['title'] = $story['story']['hed'];
			$item['timestamp'] = $story['story']['updated_at'];
			$item['uri'] = $story['template_action']['url'];
			$this->items[] = $item;
		}
	}
}
