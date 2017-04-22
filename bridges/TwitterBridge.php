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
			)
		),
		'By keyword or hashtag' => array(
			'q' => array(
				'name' => 'Keyword or #hashtag',
				'required' => true,
				'exampleValue' => 'rss-bridge, #rss-bridge',
				'title' => 'Insert a keyword or hashtag'
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
		)
	);

	public function getName(){
		switch($this->queriedContext){
		case 'By keyword or hashtag':
			$specific = 'search ';
			$param = 'q';
			break;
		case 'By username':
			$specific = '@';
			$param = 'u';
			break;
		default: return parent::getName();
		}
		return 'Twitter ' . $specific . $this->getInput($param);
	}

	public function getURI(){
		switch($this->queriedContext){
		case 'By keyword or hashtag':
			return self::URI
			. 'search?q='
			. urlencode($this->getInput('q'))
			. '&f=tweets';
		case 'By username':
			return self::URI
			. urlencode($this->getInput('u'))
			. ($this->getInput('norep') ? '' : '/with_replies');
		default: return parent::getURI();
		}
	}

	public function collectData(){
		$html = '';

		$html = getSimpleHTMLDOM($this->getURI());
		if(!$html){
			switch($this->queriedContext){
			case 'By keyword or hashtag':
				returnServerError('No results for this query.');
			case 'By username':
				returnServerError('Requested username can\'t be found.');
			}
		}

		$hidePictures = $this->getInput('nopic');

		foreach($html->find('div.js-stream-tweet') as $tweet){

			// Skip retweets?
			if($this->getInput('noretweet')
			&& $tweet->getAttribute('data-screen-name') !== $this->getInput('u')){
				continue;
			}

			$item = array();
			// extract username and sanitize
			$item['username'] = $tweet->getAttribute('data-screen-name');
			// extract fullname (pseudonym)
			$item['fullname'] = $tweet->getAttribute('data-name');
			// get author
			$item['author'] = $item['fullname'] . ' (@' . $item['username'] . ')';
			// get avatar link
			$item['avatar'] = $tweet->find('img', 0)->src;
			// get TweetID
			$item['id'] = $tweet->getAttribute('data-tweet-id');
			// get tweet link
			$item['uri'] = self::URI . $tweet->find('a.js-permalink', 0)->getAttribute('href');
			// extract tweet timestamp
			$item['timestamp'] = $tweet->find('span.js-short-timestamp', 0)->getAttribute('data-time');
			// generate the title
			$item['title'] = strip_tags(
				html_entity_decode(
					$tweet->find('p.js-tweet-text', 0)->innertext,
					ENT_QUOTES,
					'UTF-8'
				)
			);

			$this->processContentLinks($tweet);
			$this->processEmojis($tweet);

			// get tweet text
			$cleanedTweet = str_replace(
				'href="/',
				'href="' . self::URI,
				$tweet->find('p.js-tweet-text', 0)->innertext
			);

			// Add picture to content
			$picture_html = '';
			if(!$hidePictures){
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
			$image = $this->getImageURI($tweet);
			if(!$this->getInput('noimg') && !is_null($image)){
				// add enclosures
				$item['enclosures'] = array($this->getImageURI($tweet));

				$image_html = <<<EOD
<a href="{$image}">
<img
	style="align:top; max-width:558px; border:1px solid black;"
	src="{$image}" />
</a>
EOD;
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
			if($quotedTweet){
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
				$quotedImage = $this->getQuotedImageURI($tweet);
				if(!$this->getInput('noimg') && !is_null($quotedImage)){
					// add enclosures
					$item['enclosures'] = array($this->getQuotedImageURI($tweet));

					$quotedImage_html = <<<EOD
<a href="{$quotedImage}">
<img
	style="align:top; max-width:558px; border:1px solid black;"
	src="{$quotedImage}" />
</a>
EOD;
				}

				$item['content'] = <<<EOD
<div style="display: inline-block; vertical-align: top;">
	<blockquote>{$cleanedQuotedTweet}</blockquote>
</div>
<div style="display: block; vertical-align: top;">
	<blockquote>{$quotedImage_html}</blockquote>
</div>
<hr>
{$item['content']}
EOD;
			}

			// put out
			$this->items[] = $item;
		}
	}

	private function processEmojis($tweet){
		// process emojis (reduce size)
		foreach($tweet->find('img.Emoji') as $img){
			$img->style .= ' height: 1em;';
		}
	}

	private function processContentLinks($tweet){
		// processing content links
		foreach($tweet->find('a') as $link){
			if($link->hasAttribute('data-expanded-url')){
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

	private function getImageURI($tweet){
		// Find media in tweet
		$container = $tweet->find('div.AdaptiveMedia-container', 0);
		if($container && $container->find('img', 0)){
			return $container->find('img', 0)->src;
		}

		return null;
	}

	private function getQuotedImageURI($tweet){
		// Find media in tweet
		$container = $tweet->find('div.QuoteMedia-container', 0);
		if($container && $container->find('img', 0)){
			return $container->find('img', 0)->src;
		}

		return null;
	}
}
