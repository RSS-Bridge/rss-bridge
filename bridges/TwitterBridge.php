<?php
class TwitterBridge extends BridgeAbstract {
	const NAME = 'Twitter Bridge';
	const URI = 'https://twitter.com/';
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'returns tweets';
	const MAINTAINER = 'pmaziere';
	const PARAMETERS = array(
		'global' => array(
			'nopic' => array(
				'name' => 'Hide profile pictures',
				'type' => 'checkbox',
				'title' => 'Activate to hide profile pictures in content'
			),
			'noimg' => array(
				'name' => 'Hide images in tweets',
				'type' => 'checkbox',
				'title' => 'Activate to hide images in tweets'
			),
			'noimgscaling' => array(
				'name' => 'Disable image scaling',
				'type' => 'checkbox',
				'title' => 'Activate to disable image scaling in tweets (keeps original image)'
			)
		),
		'By keyword or hashtag' => array(
			'q' => array(
				'name' => 'Keyword or #hashtag',
				'required' => true,
				'exampleValue' => 'rss-bridge, #rss-bridge',
				'title' => <<<EOD
* To search for multiple words (must contain all of these words), put a space between them.

Example: `rss-bridge release`.

* To search for multiple words (contains any of these words), put "OR" between them.

Example: `rss-bridge OR rssbridge`.

* To search for an exact phrase (including whitespace), put double-quotes around them.

Example: `"rss-bridge release"`

* If you want to search for anything **but** a specific word, put a hyphen before it.

Example: `rss-bridge -release` (ignores "release")

* Of course, this also works for hashtags.

Example: `#rss-bridge OR #rssbridge`

* And you can combine them in any shape or form you like.

Example: `#rss-bridge OR #rssbridge -release`
EOD
			)
		),
		'By username' => array(
			'u' => array(
				'name' => 'username',
				'required' => true,
				'exampleValue' => 'sebsauvage',
				'title' => 'Insert a user name'
			),
			'norep' => array(
				'name' => 'Without replies',
				'type' => 'checkbox',
				'title' => 'Only return initial tweets'
			),
			'noretweet' => array(
				'name' => 'Without retweets',
				'required' => false,
				'type' => 'checkbox',
				'title' => 'Hide retweets'
			)
		),
		'By list' => array(
			'user' => array(
				'name' => 'User',
				'required' => true,
				'exampleValue' => 'sebsauvage',
				'title' => 'Insert a user name'
			),
			'list' => array(
				'name' => 'List',
				'required' => true,
				'title' => 'Insert the list name'
			),
			'filter' => array(
				'name' => 'Filter',
				'exampleValue' => '#rss-bridge',
				'required' => false,
				'title' => 'Specify term to search for'
			)
		)
	);

	public function detectParameters($url){
		$params = array();

		// By keyword or hashtag (search)
		$regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/search.*(\?|&)q=([^\/&?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['q'] = urldecode($matches[4]);
			return $params;
		}

		// By hashtag
		$regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/hashtag\/([^\/?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['q'] = urldecode($matches[3]);
			return $params;
		}

		// By list
		$regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/([^\/?\n]+)\/lists\/([^\/?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['user'] = urldecode($matches[3]);
			$params['list'] = urldecode($matches[4]);
			return $params;
		}

		// By username
		$regex = '/^(https?:\/\/)?(www\.)?twitter\.com\/([^\/?\n]+)/';
		if(preg_match($regex, $url, $matches) > 0) {
			$params['u'] = urldecode($matches[3]);
			return $params;
		}

		return null;
	}

	public function getName(){
		switch($this->queriedContext) {
		case 'By keyword or hashtag':
			$specific = 'search ';
			$param = 'q';
			break;
		case 'By username':
			$specific = '@';
			$param = 'u';
			break;
		case 'By list':
			return $this->getInput('list') . ' - Twitter list by ' . $this->getInput('user');
		default: return parent::getName();
		}
		return 'Twitter ' . $specific . $this->getInput($param);
	}

	public function getURI(){
		switch($this->queriedContext) {
		case 'By keyword or hashtag':
			return self::URI
			. 'search?q='
			. urlencode($this->getInput('q'))
			. '&f=tweets';
		case 'By username':
			return self::URI
			. urlencode($this->getInput('u'));
			// Always return without replies!
			// . ($this->getInput('norep') ? '' : '/with_replies');
		case 'By list':
			return self::URI
			. urlencode($this->getInput('user'))
			. '/lists/'
			. str_replace(' ', '-', strtolower($this->getInput('list')));
		default: return parent::getURI();
		}
	}

	public function collectData(){
		$html = '';
		$page = $this->getURI();

		if(php_sapi_name() === 'cli' && empty(ini_get('curl.cainfo'))) {
			$cookies = $this->getCookies($page);
			$html = getSimpleHTMLDOM($page, array("Cookie: $cookies"));
		} else {
			$html = getSimpleHTMLDOM($page, array(), array(CURLOPT_COOKIEFILE => ''));
		}

		if(!$html) {
			switch($this->queriedContext) {
			case 'By keyword or hashtag':
				returnServerError('No results for this query.');
			case 'By username':
				returnServerError('Requested username can\'t be found.');
			case 'By list':
				returnServerError('Requested username or list can\'t be found');
			}
		}

		$hidePictures = $this->getInput('nopic');

		foreach($html->find('div.js-stream-tweet') as $tweet) {

			// Skip retweets?
			if($this->getInput('noretweet')
			&& $tweet->find('div.context span.js-retweet-text a', 0)) {
				continue;
			}

			// remove 'invisible' content
			foreach($tweet->find('.invisible') as $invisible) {
				$invisible->outertext = '';
			}

			// Skip protmoted tweets
			$heading = $tweet->previousSibling();
			if(!is_null($heading) &&
				$heading->getAttribute('class') === 'promoted-tweet-heading'
			) {
				continue;
			}

			$item = array();
			// extract username and sanitize
			$item['username'] = htmlspecialchars_decode($tweet->getAttribute('data-screen-name'), ENT_QUOTES);
			// extract fullname (pseudonym)
			$item['fullname'] = htmlspecialchars_decode($tweet->getAttribute('data-name'), ENT_QUOTES);
			// get author
			$item['author'] = $item['fullname'] . ' (@' . $item['username'] . ')';
			if($rt = $tweet->find('div.context span.js-retweet-text a', 0)) {
				$item['author'] .= ' RT: @' . $rt->plaintext;
			}
			// get avatar link
			$item['avatar'] = $tweet->find('img', 0)->src;
			// get TweetID
			$item['id'] = $tweet->getAttribute('data-tweet-id');
			// get tweet link
			$item['uri'] = self::URI . substr($tweet->find('a.js-permalink', 0)->getAttribute('href'), 1);
			// extract tweet timestamp
			$item['timestamp'] = $tweet->find('span.js-short-timestamp', 0)->getAttribute('data-time');
			// generate the title
			$item['title'] = strip_tags($this->fixAnchorSpacing(htmlspecialchars_decode(
				$tweet->find('p.js-tweet-text', 0), ENT_QUOTES), '<a>'));

			switch($this->queriedContext) {
				case 'By list':
					// Check if filter applies to list (using raw content)
					if($this->getInput('filter')) {
						if(stripos($tweet->find('p.js-tweet-text', 0)->plaintext, $this->getInput('filter')) === false) {
							continue 2; // switch + for-loop!
						}
					}
					break;
				default:
			}

			$this->processContentLinks($tweet);
			$this->processEmojis($tweet);

			// get tweet text
			$cleanedTweet = str_replace(
				'href="/',
				'href="' . self::URI,
				$tweet->find('p.js-tweet-text', 0)->innertext
			);

			// fix anchors missing spaces in-between
			$cleanedTweet = $this->fixAnchorSpacing($cleanedTweet);

			// Add picture to content
			$picture_html = '';
			if(!$hidePictures) {
				$picture_html = <<<EOD
<a href="https://twitter.com/{$item['username']}">
<img
	style="align:top; width:75px; border:1px solid black;"
	alt="{$item['username']}"
	src="{$item['avatar']}"
	title="{$item['fullname']}" />
</a>
EOD;
			}

			// Add embeded image to content
			$image_html = '';
			$images = $this->getImageURI($tweet);
			if(!$this->getInput('noimg') && !is_null($images)) {

				foreach ($images as $image) {

					// Set image scaling
					$image_orig = $this->getInput('noimgscaling') ? $image : $image . ':orig';
					$image_thumb = $this->getInput('noimgscaling') ? $image : $image . ':thumb';

					// add enclosures
					$item['enclosures'][] = $image_orig;

					$image_html .= <<<EOD
<a href="{$image_orig}">
<img
	style="align:top; max-width:558px; border:1px solid black;"
	src="{$image_thumb}" />
</a>
EOD;
				}
			}

			// add content
			$item['content'] = <<<EOD
<div style="display: inline-block; vertical-align: top;">
	{$picture_html}
</div>
<div style="display: inline-block; vertical-align: top;">
	<blockquote>{$cleanedTweet}</blockquote>
</div>
<div style="display: block; vertical-align: top;">
	<blockquote>{$image_html}</blockquote>
</div>
EOD;

			// add quoted tweet
			$quotedTweet = $tweet->find('div.QuoteTweet', 0);
			if($quotedTweet) {
				// get tweet text
				$cleanedQuotedTweet = str_replace(
					'href="/',
					'href="' . self::URI,
					$quotedTweet->find('div.tweet-text', 0)->innertext
				);

				$this->processContentLinks($quotedTweet);
				$this->processEmojis($quotedTweet);

				// Add embeded image to content
				$quotedImage_html = '';
				$quotedImages = $this->getQuotedImageURI($tweet);

				if(!$this->getInput('noimg') && !is_null($quotedImages)) {

					foreach ($quotedImages as $image) {

						// Set image scaling
						$image_orig = $this->getInput('noimgscaling') ? $image : $image . ':orig';
						$image_thumb = $this->getInput('noimgscaling') ? $image : $image . ':thumb';

						// add enclosures
						$item['enclosures'][] = $image_orig;

						$quotedImage_html .= <<<EOD
<a href="{$image_orig}">
<img
	style="align:top; max-width:558px; border:1px solid black;"
	src="{$image_thumb}" />
</a>
EOD;
					}
				}

				$item['content'] = <<<EOD
{$item['content']}
<hr>
<div style="display: inline-block; vertical-align: top;">
	<blockquote>{$cleanedQuotedTweet}</blockquote>
</div>
<div style="display: block; vertical-align: top;">
	<blockquote>{$quotedImage_html}</blockquote>
</div>
EOD;
			}
			$item['content'] = htmlspecialchars_decode($item['content'], ENT_QUOTES);

			// put out
			$this->items[] = $item;
		}
	}

	private function processEmojis($tweet){
		// process emojis (reduce size)
		foreach($tweet->find('img.Emoji') as $img) {
			$img->style .= ' height: 1em;';
		}
	}

	private function processContentLinks($tweet){
		// processing content links
		foreach($tweet->find('a') as $link) {
			if($link->hasAttribute('data-expanded-url')) {
				$link->href = $link->getAttribute('data-expanded-url');
			}
			$link->removeAttribute('data-expanded-url');
			$link->removeAttribute('data-query-source');
			$link->removeAttribute('rel');
			$link->removeAttribute('class');
			$link->removeAttribute('target');
			$link->removeAttribute('title');
		}
	}

	private function fixAnchorSpacing($content){
		// fix anchors missing spaces in-between
		return str_replace(
			'<a',
			' <a',
			$content
		);
	}

	private function getImageURI($tweet){
		// Find media in tweet
		$images = array();

		$container = $tweet->find('div.AdaptiveMedia-container', 0);

		if($container && $container->find('img', 0)) {
			foreach ($container->find('img') as $img) {
				$images[] = $img->src;
			}
		}

		if (!empty($images)) {
			return $images;
		}

		return null;
	}

	private function getQuotedImageURI($tweet){
		// Find media in tweet
		$images = array();

		$container = $tweet->find('div.QuoteMedia-container', 0);

		if($container && $container->find('img', 0)) {
			foreach ($container->find('img') as $img) {
				$images[] = $img->src;
			}
		}

		if (!empty($images)) {
			return $images;
		}

		return null;
	}

	private function getCookies($pageURL){

		$ctx = stream_context_create(array(
			'http' => array(
				'follow_location' => false
				)
			)
		);
		$a = file_get_contents($pageURL, 0, $ctx);

		//First request to get the cookie
		$cookies = '';
		foreach($http_response_header as $hdr) {
			if(stripos($hdr, 'Set-Cookie') !== false) {
				$cLine = explode(':', $hdr)[1];
				$cLine = explode(';', $cLine)[0];
				$cookies .= ';' . $cLine;
			}
		}

		return substr($cookies, 2);
	}
}
