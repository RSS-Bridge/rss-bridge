<?php
class FacebookBridge extends BridgeAbstract {

	const MAINTAINER = 'teromene, logmanoriginal';
	const NAME = 'Facebook Bridge';
	const URI = 'https://www.facebook.com/';
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'Input a page title or a profile log. For a profile log,
 please insert the parameter as follow : myExamplePage/132621766841117';

	const PARAMETERS = array(
		'User' => array(
			'u' => array(
				'name' => 'Username',
				'required' => true
			),
			'media_type' => array(
				'name' => 'Media type',
				'type' => 'list',
				'required' => false,
				'values' => array(
					'All' => 'all',
					'Video' => 'video',
					'No Video' => 'novideo'
				),
				'defaultValue' => 'all'
			),
			'skip_reviews' => array(
				'name' => 'Skip reviews',
				'type' => 'checkbox',
				'required' => false,
				'defaultValue' => false,
				'title' => 'Feed includes reviews when checked'
			)
		),
		'Group' => array(
			'g' => array(
				'name' => 'Group',
				'type' => 'text',
				'required' => true,
				'exampleValue' => 'https://www.facebook.com/groups/743149642484225',
				'title' => 'Insert group name or facebook group URL'
			)
		),
		'global' => array(
			'limit' => array(
				'name' => 'Limit',
				'type' => 'number',
				'required' => false,
				'title' => 'Specify the number of items to return (default: -1)',
				'defaultValue' => -1
			)
		)
	);

	private $authorName = '';
	private $groupName = '';

	public function getIcon() {
		return 'https://static.xx.fbcdn.net/rsrc.php/yo/r/iRmz9lCMBD2.ico';
	}

	public function getName(){

		switch($this->queriedContext) {

			case 'User':
				if(!empty($this->authorName)) {
					return isset($this->extraInfos['name']) ? $this->extraInfos['name'] : $this->authorName
					. ' - ' . static::NAME;
				}
				break;

			case 'Group':
				if(!empty($this->groupName)) {
					return $this->groupName . ' - ' . static::NAME;
				}
				break;

		}

		return parent::getName();
	}

	public function getURI() {
		$uri = self::URI;

		switch($this->queriedContext) {

			case 'Group':
				// Discover groups via	https://www.facebook.com/groups/
				// Example group:		https://www.facebook.com/groups/sailors.worldwide
				$uri .= 'groups/' . $this->sanitizeGroup(filter_var($this->getInput('g'), FILTER_SANITIZE_URL));
				break;

			case 'User':
				// Example user 1:		https://www.facebook.com/artetv/
				// Example user 2:		artetv
				$user = $this->sanitizeUser($this->getInput('u'));

				if(!strpos($user, '/')) {
					$uri .= urlencode($user) . '/posts';
				} else {
					$uri .= 'pages/' . $user;
				}

				break;

		}

		// Request the mobile version to reduce page size (no javascript)
		// More information: https://stackoverflow.com/a/11103592
		return $uri .= '?_fb_noscript=1';
	}

	public function collectData() {

		switch($this->queriedContext) {

			case 'Group':
				$this->collectGroupData();
				break;

			case 'User':
				$this->collectUserData();
				break;

			default:
				returnClientError('Unknown context: "' . $this->queriedContext . '"!');

		}

		$limit = $this->getInput('limit') ?: -1;

		if($limit > 0 && count($this->items) > $limit) {
			$this->items = array_slice($this->items, 0, $limit);
		}

	}

	#region Group

	private function collectGroupData() {

		$header = array('Accept-Language: ' . getEnv('HTTP_ACCEPT_LANGUAGE') . "\r\n");

		$html = getSimpleHTMLDOM($this->getURI(), $header)
			or returnServerError('Failed loading facebook page: ' . $this->getURI());

		if(!$this->isPublicGroup($html)) {
			returnClientError('This group is not public! RSS-Bridge only supports public groups!');
		}

		defaultLinkTo($html, substr(self::URI, 0, strlen(self::URI) - 1));

		$this->groupName = $this->extractGroupName($html);

		$posts = $html->find('div.userContentWrapper')
			or returnServerError('Failed finding posts!');

		foreach($posts as $post) {

			$item = array();

			$item['uri'] = $this->extractGroupURI($post);
			$item['title'] = $this->extractGroupTitle($post);
			$item['author'] = $this->extractGroupAuthor($post);
			$item['content'] = $this->extractGroupContent($post);
			$item['timestamp'] = $this->extractGroupTimestamp($post);
			$item['enclosures'] = $this->extractGroupEnclosures($post);

			$this->items[] = $item;

		}

	}

	private function sanitizeGroup($group) {

		if(filter_var(
			$group,
			FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {
			// User provided a URL

			$urlparts = parse_url($group);

			if($urlparts['host'] !== parse_url(self::URI)['host']
			&& 'www.' . $urlparts['host'] !== parse_url(self::URI)['host']) {

				returnClientError('The host you provided is invalid! Received "'
				. $urlparts['host']
				. '", expected "'
				. parse_url(self::URI)['host']
				. '"!');

			}

			return explode('/', $urlparts['path'])[2];

		} elseif(strpos($group, '/') !== false) {
			returnClientError('The group you provided is invalid: ' . $group);
		} else {
			return $group;
		}

	}

	private function isPublicGroup($html) {

		// Facebook redirects to the groups about page for non-public groups
		$about = $html->find('#pagelet_group_about', 0);

		return !($about);

	}

	private function extractGroupName($html) {

		$ogtitle = $html->find('meta[property="og:title"]', 0)
			or returnServerError('Unable to find group title!');

		return htmlspecialchars_decode($ogtitle->content, ENT_QUOTES);

	}

	private function extractGroupURI($post) {

		$elements = $post->find('a')
			or returnServerError('Unable to find URI!');

		foreach($elements as $anchor) {

			// Find the one that is a permalink
			if(strpos($anchor->href, 'permalink') !== false) {
				return $anchor->href;
			}

		}

		return null;

	}

	private function extractGroupContent($post) {

		$content = $post->find('div.userContent', 0)
			or returnServerError('Unable to find user content!');

		return $content->innertext . $content->next_sibling()->innertext;

	}

	private function extractGroupTimestamp($post) {

		$element = $post->find('abbr[data-utime]', 0)
			or returnServerError('Unable to find timestamp!');

		return $element->getAttribute('data-utime');

	}

	private function extractGroupAuthor($post) {

		$element = $post->find('img', 0)
			or returnServerError('Unable to find author information!');

		return $element->{'aria-label'};

	}

	private function extractGroupEnclosures($post) {

		$elements = $post->find('div.userContent', 0)->next_sibling()->find('img');

		$enclosures = array();

		foreach($elements as $enclosure) {
			$enclosures[] = $enclosure->src;
		}

		return empty($enclosures) ? null : $enclosures;

	}

	private function extractGroupTitle($post) {

		$element = $post->find('h5', 0)
			or returnServerError('Unable to find title!');

		if(strpos($element->plaintext, 'shared') === false) {

			$content = strip_tags($this->extractGroupContent($post));

			return $this->extractGroupAuthor($post)
			. ' posted: '
			. substr(
					$content,
					0,
					strpos(wordwrap($content, 64), "\n")
				)
			. '...';

		}

		return $element->plaintext;

	}

	#endregion (Group)

	#region User

	/**
	 * Checks if $user is a valid username or URI and returns the username
	 */
	private function sanitizeUser($user) {
		if (filter_var($user, FILTER_VALIDATE_URL)) {

			$urlparts = parse_url($user);

			if($urlparts['host'] !== parse_url(self::URI)['host']) {
				returnClientError('The host you provided is invalid! Received "'
				. $urlparts['host']
				. '", expected "'
				. parse_url(self::URI)['host']
				. '"!');
			}

			if(!array_key_exists('path', $urlparts)
			|| $urlparts['path'] === '/') {
				returnClientError('The URL you provided doesn\'t contain the user name!');
			}

			return explode('/', $urlparts['path'])[1];

		} else {

			// First character cannot be a forward slash
			if(strpos($user, '/') === 0) {
				returnClientError('Remove leading slash "/" from the username!');
			}

			return $user;

		}
	}

	/**
	 * Bypass external link redirection
	 */
	private function unescape_fb_link($content){
		return preg_replace_callback('/ href=\"([^"]+)\"/i', function($matches){
			if(is_array($matches) && count($matches) > 1) {

				$link = $matches[1];

				if(strpos($link, 'facebook.com/l.php?u=') !== false)
					$link = urldecode(extractFromDelimiters($link, 'facebook.com/l.php?u=', '&'));

				return ' href="' . $link . '"';

			}
		}, $content);
	}

	/**
	 * Remove Facebook's tracking code
	 */
	private function remove_tracking_codes($content){
		return preg_replace_callback('/ href=\"([^"]+)\"/i', function($matches){
			if(is_array($matches) && count($matches) > 1) {

				$link = $matches[1];

				if(strpos($link, 'facebook.com') !== false) {
					if(strpos($link, '?') !== false) {
						$link = substr($link, 0, strpos($link, '?'));
					}
				}
				return ' href="' . $link . '"';

			}
		}, $content);
	}

	/**
	 * Convert textual representation of emoticons back to ASCII emoticons.
	 * i.e. "<i><u>smile emoticon</u></i>" => ":)"
	 */
	private function unescape_fb_emote($content){
		return preg_replace_callback('/<i><u>([^ <>]+) ([^<>]+)<\/u><\/i>/i', function($matches){
				static $facebook_emoticons = array(
					'smile' => ':)',
					'frown' => ':(',
					'tongue' => ':P',
					'grin' => ':D',
					'gasp' => ':O',
					'wink' => ';)',
					'pacman' => ':<',
					'grumpy' => '>_<',
					'unsure' => ':/',
					'cry' => ':\'(',
					'kiki' => '^_^',
					'glasses' => '8-)',
					'sunglasses' => 'B-)',
					'heart' => '<3',
					'devil' => ']:D',
					'angel' => '0:)',
					'squint' => '-_-',
					'confused' => 'o_O',
					'upset' => 'xD',
					'colonthree' => ':3',
					'like' => '&#x1F44D;');

				$len = count($matches);

				if ($len > 1)
					for ($i = 1; $i < $len; $i++)
						foreach ($facebook_emoticons as $name => $emote)
							if ($matches[$i] === $name)
								return $emote;

				return $matches[0];
			}, $content);
	}

	/**
	 * Returns the captcha message for the given captcha
	 */
	private function returnCaptchaMessage($captcha) {
		// Save form for submitting after getting captcha response
		if (session_status() == PHP_SESSION_NONE) {
			session_start();
		}

		$captcha_fields = array();

		foreach ($captcha->find('input, button') as $input) {
			$captcha_fields[$input->name] = $input->value;
		}

		$_SESSION['captcha_fields'] = $captcha_fields;
		$_SESSION['captcha_action'] = $captcha->find('form', 0)->action;

		// Show captcha filling form to the viewer, proxying the captcha image
		$img = base64_encode(getContents($captcha->find('img', 0)->src));

		header('Content-Type: text/html', true, 500);

		$message = <<<EOD
<form method="post" action="?{$_SERVER['QUERY_STRING']}">
<h2>Facebook captcha challenge</h2>
<p>Unfortunately, rss-bridge cannot fetch the requested page.<br />
Facebook wants rss-bridge to resolve the following captcha:</p>
<p><img src="data:image/png;base64,{$img}" /></p>
<p><b>Response:</b> <input name="captcha_response" placeholder="please fill in" />
<input type="submit" value="Submit!" /></p>
</form>
EOD;

		die($message);
	}

	/**
	 * Checks if a capture response was received and tries to load the contents
	 * @return mixed null if no capture response was received, simplhtmldom document otherwise
	 */
	private function handleCaptchaResponse() {
		if (isset($_POST['captcha_response'])) {
			if (session_status() == PHP_SESSION_NONE)
				session_start();

			if (isset($_SESSION['captcha_fields'], $_SESSION['captcha_action'])) {
				$captcha_action = $_SESSION['captcha_action'];
				$captcha_fields = $_SESSION['captcha_fields'];
				$captcha_fields['captcha_response'] = preg_replace('/[^a-zA-Z0-9]+/', '', $_POST['captcha_response']);

				$header = array(
					'Content-type: application/x-www-form-urlencoded',
					'Referer: ' . $captcha_action,
					'Cookie: noscript=1'
				);

				$opts = array(
					CURLOPT_POST => 1,
					CURLOPT_POSTFIELDS => http_build_query($captcha_fields)
				);

				$html = getSimpleHTMLDOM($captcha_action, $header, $opts)
					or returnServerError('Failed to submit captcha response back to Facebook');

				return $html;
			}

			unset($_SESSION['captcha_fields']);
			unset($_SESSION['captcha_action']);
		}

		return null;
	}

	private function collectUserData(){

		$html = $this->handleCaptchaResponse();

		// Retrieve page contents
		if(is_null($html)) {

			$header = array('Accept-Language: ' . getEnv('HTTP_ACCEPT_LANGUAGE'));

			$html = getSimpleHTMLDOM($this->getURI(), $header)
				or returnServerError('No results for this query.');

		}

		// Handle captcha form?
		$captcha = $html->find('div.captcha_interstitial', 0);

		if (!is_null($captcha)) {
			$this->returnCaptchaMessage($captcha);
		}

		// No captcha? We can carry on retrieving page contents :)
		// First, we check wether the page is public or not
		$loginForm = $html->find('._585r', 0);

		if($loginForm != null) {
			returnServerError('You must be logged in to view this page. This is not supported by RSS-Bridge.');
		}

		$element = $html
		->find('#pagelet_timeline_main_column')[0]
		->children(0)
		->children(0)
		->next_sibling()
		->children(0);

		if(isset($element)) {

			$author = str_replace(' - Posts | Facebook', '', $html->find('title#pageTitle', 0)->innertext);

			$profilePic = $html->find('meta[property="og:image"]', 0)->content;

			$this->authorName = $author;

			foreach($element->children() as $cell) {
				// Manage summary posts
				if(strpos($cell->class, '_3xaf') !== false) {
					$posts = $cell->children();
				} else {
					$posts = array($cell);
				}

				// Optionally skip reviews
				if($this->getInput('skip_reviews')
				&& !is_null($cell->find('#review_composer_container', 0))) {
					continue;
				}

				foreach($posts as $post) {
					// Check media type
					switch($this->getInput('media_type')) {
						case 'all': break;
						case 'video':
							if(empty($post->find('[aria-label=Video]'))) continue 2;
							break;
						case 'novideo':
							if(!empty($post->find('[aria-label=Video]'))) continue 2;
							break;
						default: break;
					}

					$item = array();

					if(count($post->find('abbr')) > 0) {

						$content = $post->find('.userContentWrapper', 0);

						// This array specifies filters applied to all posts in order of appearance
						$content_filters = array(
							'._5mly', // Remove embedded videos (the preview image remains)
							'._2ezg', // Remove "Views ..."
							'.hidden_elem', // Remove hidden elements (they are hidden anyway)
						);

						foreach($content_filters as $filter) {
							foreach($content->find($filter) as $subject) {
								$subject->outertext = '';
							}
						}

						// Change origin tag for embedded media from div to paragraph
						foreach($content->find('._59tj') as $subject) {
							$subject->outertext = '<p>' . $subject->innertext . '</p>';
						}

						// Change title tag for embedded media from anchor to paragraph
						foreach($content->find('._3n1k a') as $anchor) {
							$anchor->outertext = '<p>' . $anchor->innertext . '</p>';
						}

						$content = preg_replace(
							'/(?i)><div class=\"_3dp([^>]+)>(.+?)div\ class=\"[^u]+userContent\"/i',
							'',
							$content);

						$content = preg_replace(
							'/(?i)><div class=\"_4l5([^>]+)>(.+?)<\/div>/i',
							'',
							$content);

						// Remove "SpSonsSoriSsés"
						$content = preg_replace(
							'/(?iU)<a [^>]+ href="#" role="link" [^>}]+>.+<\/a>/iU',
							'',
							$content);

						// Remove html nodes, keep only img, links, basic formatting
						$content = strip_tags($content, '<a><img><i><u><br><p>');

						$content = $this->unescape_fb_link($content);

						// Clean useless html tag properties and fix link closing tags
						foreach (array(
							'onmouseover',
							'onclick',
							'target',
							'ajaxify',
							'tabindex',
							'class',
							'style',
							'data-[^=]*',
							'aria-[^=]*',
							'role',
							'rel',
							'id') as $property_name) {
							$content = preg_replace('/ ' . $property_name . '=\"[^"]*\"/i', '', $content);
						}

						$content = preg_replace('/<\/a [^>]+>/i', '</a>', $content);

						$this->unescape_fb_emote($content);

						// Restore links in the post before further parsing
						$post = defaultLinkTo($post, self::URI);

						// Restore links in the content before adding to the item
						$content = defaultLinkTo($content, self::URI);

						$content = $this->remove_tracking_codes($content);

						// Retrieve date of the post
						$date = $post->find('abbr')[0];

						if(isset($date) && $date->hasAttribute('data-utime')) {
							$date = $date->getAttribute('data-utime');
						} else {
							$date = 0;
						}

						// Build title from content
						$title = strip_tags($post->find('.userContent', 0)->innertext);
						if(strlen($title) > 64)
							$title = substr($title, 0, strpos(wordwrap($title, 64), "\n")) . '...';

						$uri = $post->find('abbr')[0]->parent()->getAttribute('href');

						if (false !== strpos($uri, '?')) {
							$uri = substr($uri, 0, strpos($uri, '?'));
						}

						//Build and add final item
						$item['uri'] = htmlspecialchars_decode($uri, ENT_QUOTES);
						$item['content'] = htmlspecialchars_decode($content, ENT_QUOTES);
						$item['title'] = htmlspecialchars_decode($title, ENT_QUOTES);
						$item['author'] = htmlspecialchars_decode($author, ENT_QUOTES);
						$item['timestamp'] = $date;

						if(strpos($item['content'], '<img') === false) {
							$item['enclosures'] = array($profilePic);
						}

						$this->items[] = $item;
					}
				}
			}
		}
	}
	#endregion (User)

}
