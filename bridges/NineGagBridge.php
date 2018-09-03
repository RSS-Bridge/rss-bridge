<?php

class NineGagBridge extends BridgeAbstract {
	const NAME = '9gag Bridge';
	const URI = 'https://9gag.com/';
	const DESCRIPTION = 'Returns latest quotes from 9gag.';
	const MAINTAINER = 'ZeNairolf';
	const CACHE_TIMEOUT = 0;
	const PARAMETERS = array(
		'Popular' => array(
			'd' => array(
				'name' => 'Section',
				'type' => 'list',
				'required' => true,
				'values' => array(
					'Hot' => 'hot',
					'Trending' => 'trending',
					'Fresh' => 'fresh',
				),
			),
			'p' => array(
				'name' => 'Pages',
				'type' => 'number',
				'defaultValue' => 3,
			),
		),
		'Sections' => array(
			'g' => array(
				'name' => 'Section',
				'type' => 'list',
				'required' => true,
				'values' => array(
					'Animals' => 'cute',
					'Anime & Manga' => 'anime-manga',
					'Ask 9GAG' => 'ask9gag',
					'Awesome' => 'awesome',
					'Basketball' => 'basketball',
					'Car' => 'car',
					'Classical Art Memes' => 'classicalartmemes',
					'Comic' => 'comic',
					'Cosplay' => 'cosplay',
					'Countryballs' => 'country',
					'DIY & Crafts' => 'imadedis',
					'Drawing & Illustration' => 'drawing',
					'Fan Art' => 'animefanart',
					'Food & Drinks' => 'food',
					'Football' => 'football',
					'Fortnite' => 'fortnite',
					'Funny' => 'funny',
					'GIF' => 'gif',
					'Gaming' => 'gaming',
					'Girl' => 'girl',
					'Girly Things' => 'girly',
					'Guy' => 'guy',
					'History' => 'history',
					'Home Design' => 'home',
					'Horror' => 'horror',
					'K-Pop' => 'kpop',
					'LEGO' => 'lego',
					'League of Legends' => 'leagueoflegends',
					'Movie & TV' => 'movie-tv',
					'Music' => 'music',
					'NFK - Not For Kids' => 'nsfw',
					'Overwatch' => 'overwatch',
					'PC Master Race' => 'pcmr',
					'PUBG' => 'pubg',
					'Pic Of The Day' => 'photography',
					'Pokémon' => 'pokemon',
					'Politics' => 'politics',
					'Relationship' => 'relationship',
					'Roast Me' => 'roastme',
					'Satisfying' => 'satisfying',
					'Savage' => 'savage',
					'School' => 'school',
					'Sci-Tech' => 'science',
					'Sport' => 'sport',
					'Star Wars' => 'starwars',
					'Superhero' => 'superhero',
					'Surreal Memes' => 'surrealmemes',
					'Timely' => 'timely',
					'Travel' => 'travel',
					'Video' => 'video',
					'WTF' => 'wtf',
					'Wallpaper' => 'wallpaper',
					'Warhammer' => 'warhammer',
				),
			),
			't' => array(
				'name' => 'Type',
				'type' => 'list',
				'required' => true,
				'values' => array(
					'Hot' => 'hot',
					'Fresh' => 'fresh',
				),
			),
			'p' => array(
				'name' => 'Pages',
				'type' => 'number',
				'defaultValue' => 3,
			),
		),
	);

	const MIN_NBR_PAGE = 1;
	const MAX_NBR_PAGE = 6;

	protected $p = null;

	public function collectData() {
		$url = sprintf(
			'%sv1/group-posts/group/%s/type/%s?',
			self::URI,
			$this->getGroup(),
			$this->getType()
		);
		$cursor = 'c=10';
		$posts = array();
		for ($i = 0; $i < $this->getPages(); ++$i) {
			$content = getContents($url.$cursor);
			$json = json_decode($content, true);
			$posts = array_merge($posts, $json['data']['posts']);
			$cursor = $json['data']['nextCursor'];
		}

		foreach ($posts as $post) {
			$item['uri'] = $post['url'];
			$item['title'] = $post['title'];
			$item['content'] = self::getContent($post);
			$item['categories'] = self::getCategories($post);
			$item['timestamp'] = self::getTimestamp($post);

			$this->items[] = $item;
		}
	}

	public function getName() {
		if ($_GET['bridge'] === substr(get_class($this), 0, -6)) {
			if ($this->getInput('d')) {
				$name = $this->getParameterKey('d');
			} else {
				$name = sprintf(
					'%s [%s]',
					$this->getParameterKey('g'),
					$this->getParameterKey('t')
				);
			}

			return sprintf('%s - %s', '9GAG', $name);
		}

		return self::NAME;
	}

	public function getURI() {
		$uri = $this->getInput('g');
		if ('default' === $uri) {
			$uri = $this->getInput('t');
		}

		return self::URI.$uri;
	}

	public function getIcon() {
		return sprintf(
			'%s%s',
			'https://www.google.com/s2/favicons?domain=',
			parse_url(self::URI, PHP_URL_HOST)
		);
	}

	protected function getGroup() {
		if ($this->getInput('d')) {
			return 'default';
		}

		return $this->getInput('g');
	}

	protected function getType() {
		if ($this->getInput('d')) {
			return $this->getInput('d');
		}

		return $this->getInput('t');
	}

	protected function getPages() {
		if (null === $this->p) {
			$value = (int) $this->getInput('p');
			$value = ($value < self::MIN_NBR_PAGE) ? self::MIN_NBR_PAGE : $value;
			$value = ($value > self::MAX_NBR_PAGE) ? self::MAX_NBR_PAGE : $value;

			$this->p = $value;
		}

		return $this->p;
	}

	protected function getParameterKey(String $input = null) {
		$params = $this->getParameters();
		$tab = 'Sections';
		if ('d' === $input) {
			$tab = 'Popular';
		}
		if (!isset($params[$tab][$input])) {
			return '';
		}

		return array_search(
			$this->getInput($input),
			$params[$tab][$input]['values']
		);
	}

	protected static function getContent(array $post) {
		if ('Animated' === $post['type']) {
			$content = self::getAnimated($post);
		} else {
			$content = self::getPhoto($post);
		}

		return $content;
	}

	protected static function getPhoto(array $post) {
		$image = $post['images']['image460'];
		$photo = '<picture>';
		$photo .= sprintf(
			'<source srcset="%s" type="image/webp">',
			$image['webpUrl']
		);
		$photo .= sprintf(
			'<img src="%s" alt="%s" %s>',
			$image['url'],
			$post['title'],
			'width="500"'
		 );
		$photo .= '</picture>';

		return $photo;
	}

	protected static function getAnimated(array $post = array()) {
		$poster = $post['images']['image460']['url'];
		$sources = $post['images'];
		$video = sprintf(
			'<video poster="%s" %s>',
			$poster,
			'preload="auto" loop controls style="min-height: 300px" width="500"'
		);
		$video .= sprintf(
			'<source src="%s" type="video/webm">',
			$sources['image460sv']['vp9Url']
		);
		$video .= sprintf(
			'<source src="%s" type="video/mp4">',
			$sources['image460sv']['h265Url']
		);
		$video .= sprintf(
			'<source src="%s" type="video/mp4">',
			$sources['image460svwm']['url']
		);
		$video .= '</video>';

		return $video;
	}

	protected static function getCategories(array $post) {
		$params = self::PARAMETERS;
		$sections = $params['Sections']['g']['values'];
		$postSections = $post['sections'];
		foreach ($postSections as $key => $section) {
			$postSections[$key] = array_search($section, $sections);
		}

		return $postSections;
	}

	protected static function getTimestamp(array $post) {
		$url = $post['images']['image460']['url'];
		$headers = get_headers($url, true);
		$date = $headers['Date'];
		$time = strtotime($date);

		return $time;
	}
}
