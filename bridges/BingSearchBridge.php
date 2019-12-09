<?php

class BingSearchBridge extends BridgeAbstract
{
	const NAME = 'Bing search';
	const URI = 'https://www.bing.com/';
	const DESCRIPTION = 'Return images from bing search discover';
	const MAINTAINER = 'DnAp';
	const PARAMETERS = array(
		'Image Discover' => array(
			'category' => array(
				'name' => 'Categories',
				'type' => 'list',
				'values' => self::IMAGE_DISCOVER_CATEGORIES
			),
			'image_size' => array(
				'name' => 'Image size',
				'type' => 'list',
				'values' => array(
					'Small' => 'turl',
					'Full size' => 'imgurl'
				)
			)
		)
	);

	const IMAGE_DISCOVER_CATEGORIES = array(
		'Abstract' => 'abstract',
		'Animals' => 'animals',
		'Anime' => 'anime',
		'Architecture' => 'architecture',
		'Arts and Crafts' => 'arts-and-crafts',
		'Beauty' => 'beauty',
		'Cars and Motorcycles' => 'cars-and-motorcycles',
		'Cats' => 'cats',
		'Celebrities' => 'celebrities',
		'Comics' => 'comics',
		'DIY' => 'diy',
		'Dogs' => 'dogs',
		'Fitness' => 'fitness',
		'Food and Drink' => 'food-and-drink',
		'Funny' => 'funny',
		'Gadgets' => 'gadgets',
		'Gardening' => 'gardening',
		'Geeky' => 'geeky',
		'Hairstyles' => 'hairstyles',
		'Home Decor' => 'home-decor',
		'Marine Life' => 'marine-life',
		'Men\'s Fashion' => 'men%27s-fashion',
		'Nature' => 'nature',
		'Outdoors' => 'outdoors',
		'Parenting' => 'parenting',
		'Phone Wallpapers' => 'phone-wallpapers',
		'Photography' => 'photography',
		'Quotes' => 'quotes',
		'Recipes' => 'recipes',
		'Snow' => 'snow',
		'Tattoos' => 'tattoos',
		'Travel' => 'travel',
		'Video Games' => 'video-games',
		'Weddings' => 'weddings',
		'Women\'s Fashion' => 'women%27s-fashion',
	);

	public function getIcon()
	{
		return 'https://www.bing.com/sa/simg/bing_p_rr_teal_min.ico';
	}

	public function collectData()
	{
		$this->items = $this->imageDiscover($this->getInput('category'));
	}

	public function getName()
	{
		if ($this->getInput('category')) {
			if (self::IMAGE_DISCOVER_CATEGORIES[$this->getInput('categories')] !== null) {
				$category = self::IMAGE_DISCOVER_CATEGORIES[$this->getInput('categories')];
			} else {
				$category = 'Unknown';
			}

			return 'Best ' . $category . ' - Bing Image Discover';
		}
		return parent::getName();
	}

	private function imageDiscover($category)
	{
		$html = getSimpleHTMLDOM(self::URI . '/discover/' . $category)
		or returnServerError('Could not request ' . self::NAME);
		$sizeKey = $this->getInput('image_size');

		$items = array();
		foreach ($html->find('a.iusc') as $element) {
			$data = json_decode(htmlspecialchars_decode($element->getAttribute('m')), true);

			$item = array();
			$item['title'] = basename(rtrim($data['imgurl'], '/'));
			$item['uri'] = $data['imgurl'];
			$item['content'] = '<a href="' . $data['imgurl'] . '">
				<img src="' . $data[$sizeKey] . '" alt="' . $item['title'] . '"></a>
				<p>Source: <a href="' . $this->curUrl($data['surl']) . '"> </a></p>';
			$item['enclosures'] = $data['imgurl'];

			$items[] = $item;
		}
		return $items;
	}

	private function curUrl($url)
	{
		if (strlen($url) <= 80) {
			return $url;
		}
		return substr($url, 0, 80) . '...';
	}
}
