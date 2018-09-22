<?php

class NineGagBridge extends BridgeAbstract {
	const NAME = '9gag Bridge';
	const URI = 'https://9gag.com/';
	const DESCRIPTION = 'Returns latest quotes from 9gag.';
	const MAINTAINER = 'ZeNairolf';
	const CACHE_TIMEOUT = 3600;
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
		if ($this->getInput('d')) {
			$name = sprintf('%s - %s', '9GAG', $this->getParameterKey('d'));
		} elseif ($this->getInput('g')) {
			$name = sprintf('%s - %s', '9GAG', $this->getParameterKey('g'));
			if ($this->getInput('t')) {
				$name = sprintf('%s [%s]', $name, $this->getParameterKey('t'));
			}
		}
		if (!empty($name)) {
			return $name;
		}

		return self::NAME;
	}

	public function getURI() {
		$uri = $this->getInput('g');
		if ($uri === 'default') {
			$uri = $this->getInput('t');
		}

		return self::URI.$uri;
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
		if ($this->p === null) {
			$value = (int) $this->getInput('p');
			$value = ($value < self::MIN_NBR_PAGE) ? self::MIN_NBR_PAGE : $value;
			$value = ($value > self::MAX_NBR_PAGE) ? self::MAX_NBR_PAGE : $value;

			$this->p = $value;
		}

		return $this->p;
	}

	protected function getParameterKey($input = '') {
		$params = $this->getParameters();
		$tab = 'Sections';
		if ($input === 'd') {
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

	protected static function getContent($post) {
		if ($post['type'] === 'Animated') {
			$content = self::getAnimated($post);
		} elseif ($post['type'] === 'Article') {
			$content = self::getArticle($post);
		} else {
			$content = self::getPhoto($post);
		}

		return $content;
	}

	protected static function getPhoto($post) {
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

	protected static function getAnimated($post) {
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

	protected static function getArticle($post) {
		$blocks = $post['article']['blocks'];
		$medias = $post['article']['medias'];
		$contents = array();
		foreach ($blocks as $block) {
			if ('Media' === $block['type']) {
				$mediaId = $block['mediaId'];
				$contents[] = self::getContent($medias[$mediaId]);
			} elseif ('RichText' === $block['type']) {
				$contents[] = self::getRichText($block['content']);
			}
		}

		$content = join('</div><div>', $contents);
		$content = sprintf(
			'<%1$s>%2$s</%1$s>',
			'div',
			$content
		);

		return $content;
	}

	protected static function getRichText($text = '') {
		$text = trim($text);

		if (preg_match('/^>\s(?<text>.*)/', $text, $matches)) {
			$text = sprintf(
				'<%1$s>%2$s</%1$s>',
				'blockquote',
				$matches['text']
			);
		} else {
			$text = sprintf(
				'<%1$s>%2$s</%1$s>',
				'p',
				$text
			);
		}

		return $text;
	}

	protected static function getCategories($post) {
		$params = self::PARAMETERS;
		$sections = $params['Sections']['g']['values'];

		if(isset($post['sections'])) {
			$postSections = $post['sections'];
		} elseif (isset($post['postSection'])) {
			$postSections = array($post['postSection']);
		} else {
			$postSections = array();
		}

		foreach ($postSections as $key => $section) {
			$postSections[$key] = array_search($section, $sections);
		}

		return $postSections;
	}

	protected static function getTimestamp($post) {
		$url = $post['images']['image460']['url'];
		$headers = get_headers($url, true);
		$date = $headers['Date'];
		$time = strtotime($date);

		return $time;
	}
}
