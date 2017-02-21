<?php
require __DIR__ . '/../lib/contents_curl.php';
class FacebookBridge extends BridgeAbstract {

	const MAINTAINER = 'teromene';
	const NAME = 'Facebook';
	const URI = 'https://www.facebook.com/';
	const CACHE_TIMEOUT = 300; // 5min
	const DESCRIPTION = 'Input a page title or a profile log. For a profile log,
 please insert the parameter as follow : myExamplePage/132621766841117';

	const PARAMETERS = array( array(
		'u' => array(
			'name' => 'Username',
			'required' => true
		)
	));

	private $authorName = '';

	public function collectData(){

		//Extract a string using start and end delimiters
		function extractFromDelimiters($string, $start, $end){
			if(strpos($string, $start) !== false){
				$section_retrieved = substr($string, strpos($string, $start) + strlen($start));
				$section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
				return $section_retrieved;
			}

			return false;
		}

		//Utility function for cleaning a Facebook link
		$unescape_fb_link = function($matches){
			if(is_array($matches) && count($matches) > 1){
				$link = $matches[1];
				if(strpos($link, '/') === 0)
					$link = self::URI . $link . '"';
				if(strpos($link, 'facebook.com/l.php?u=') !== false)
					$link = urldecode(extractFromDelimiters($link, 'facebook.com/l.php?u=', '&'));
				return ' href="' . $link . '"';
			}
		};

		//Utility function for converting facebook emoticons
		$unescape_fb_emote = function($matches){
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
		};

		$html = null;

		//Handle captcha response sent by the viewer
		if (isset($_POST['captcha_response']))
		{
			if (session_status() == PHP_SESSION_NONE)
				session_start();
			if (isset($_SESSION['captcha_fields'], $_SESSION['captcha_action']))
			{
				$captcha_action = $_SESSION['captcha_action'];
				$captcha_fields = $_SESSION['captcha_fields'];
				$captcha_fields['captcha_response'] = preg_replace("/[^a-zA-Z0-9]+/", "", $_POST['captcha_response']);
				/*
				$http_options = array(
					'http' => array(
						'method'  => 'POST',
						'user_agent' => ini_get('user_agent'),
						'header' => array("Content-type:
 application/x-www-form-urlencoded\r\nReferer: $captcha_action\r\nCookie: noscript=1\r\n"),
						'content' => http_build_query($captcha_fields)
					),
				);
				$context = stream_context_create($http_options);
				$html = getContents($captcha_action, false, $context);
				 */
				list($html, $info, $res_header, $proxy) = curlgetContents($captcha_action, $captcha_fields, true);
				if ( $info['http_code'] != 200 )
					returnServerError('Error '.$info['http_code'].$captcha_action."\nReq:\n".$res_header."\nResp:\n".$info['request_header']);

				unset($_SESSION['captcha_fields']);
				$html = str_get_html($html);
			}
			unset($_SESSION['captcha_fields']);
			unset($_SESSION['captcha_action']);
		}

		$res_header = '';
		$proxy = '';
		//Retrieve page contents
		if (is_null($html)) {
			if (!strpos($this->getInput('u'), "/")) {
                list($html, $info, $res_header, $proxy) = curlgetSimpleHTMLDOM(self::URI.urlencode($this->getInput('u')).'?_fb_noscript=1');
				if ( $info['http_code'] != 200 )
					returnServerError('Error '.$info['http_code']."\nResp:\n".$res_header."\nReq:\n".$info['request_header']);
			} else {
                list($html, $info) = curlgetSimpleHTMLDOM(self::URI.'pages/'.$this->getInput('u').'?_fb_noscript=1');
				if ( $info['http_code'] != 200 ) returnServerError('No results for this query.');
			}
		}

		//Handle captcha form?
		$captcha = $html->find('div.captcha_interstitial', 0);
		if (!is_null($captcha))
		{
			//Save form for submitting after getting captcha response
			if (session_status() == PHP_SESSION_NONE)
				session_start();
			$captcha_fields = array();
			foreach ($captcha->find('input, button') as $input)
				$captcha_fields[$input->name] = $input->value;
			$_SESSION['captcha_fields'] = $captcha_fields;
			$_SESSION['captcha_action'] = $captcha->find('form', 0)->action;

			//Show captcha filling form to the viewer, proxying the captcha image
			$img = base64_encode(getContents($captcha->find('img', 0)->src));
			header('HTTP/1.1 500 ' . Http::getMessageForCode(500));
			header('Content-Type: text/html');
			$message = <<<EOD
<form method="post" action="?{$_SERVER['QUERY_STRING']}">
	<h2>Facebook captcha challenge</h2>
	<p>Unfortunately, rss-bridge cannot fetch the requested page.<br />
		Facebook wants rss-bridge to resolve the following captcha:</p>
	<p><img src="data:image/png;base64,{$img}" /></p>
	<p><b>Response:</b> <input name="captcha_response" placeholder="please fill in" />
	<input type="submit" value="Submit!" /></p>
	<pre>{$res_header}</pre>
	<p>Proxy: <pre>{$proxy}</pre></p>
</form>
EOD;
			die($message);
		}

		//No captcha? We can carry on retrieving page contents :)
		$element = $html
		->find('#pagelet_timeline_main_column')[0]
		->children(0)
		->children(0)
		->children(0)
		->next_sibling()
		->children(0);

		if(isset($element)){

			$author = str_replace(' | Facebook', '', $html->find('title#pageTitle', 0)->innertext);
			$profilePic = 'https://graph.facebook.com/'
			. $this->getInput('u')
			. '/picture?width=200&amp;height=200';

			$this->authorName = $author;

			foreach($element->children() as $post){
				// Ignore summary posts
				if(strpos($post->class, '_3xaf') !== false) continue;

				// Determine post attachments
				/*
				$attachment_wrapper = $post->find('._3x-2')[0];// search for attachment
				if ( isset($attachment_wrapper) ) {
					$attachment = $attachment_wrapper->find('.mtm')[0]->children(0);
					if ( strpos($attachment->class, '_2a2q') !== false ) {
						// photos
					} elseif ( strpos($attachment->class, '_6m2') !== false ) {
						// link
					}
				}*/

				$item = array();

				if(count($post->find('abbr')) > 0){

					//Retrieve post contents
					$content = preg_replace(
						'/(?i)><div class=\"clearfix([^>]+)>(.+?)div\ class=\"userContent\"/i',
						'',
						$post);

					$content = preg_replace(
						'/(?i)><div class=\"_59tj([^>]+)>(.+?)<\/div><\/div><a/i',
						'',
						$content);

					$content = preg_replace(
						'/(?i)><div class=\"_3dp([^>]+)>(.+?)div\ class=\"[^u]+userContent\"/i',
						'',
						$content);

					$content = preg_replace(
						'/(?i)><div class=\"_4l5([^>]+)>(.+?)<\/div>/i',
						'',
						$content);

					//Remove html nodes, keep only img, links, basic formatting
					$content = strip_tags($content, '<a><img><i><u><br><p>');

					//Adapt link hrefs: convert relative links into absolute links and bypass external link redirection
					$content = preg_replace_callback('/ href=\"([^"]+)\"/i', $unescape_fb_link, $content);

					//Clean useless html tag properties and fix link closing tags
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
						'id') as $property_name)
							$content = preg_replace('/ ' . $property_name . '=\"[^"]*\"/i', '', $content);
					$content = preg_replace('/<\/a [^>]+>/i', '</a>', $content);

					//Convert textual representation of emoticons eg
					//"<i><u>smile emoticon</u></i>" back to ASCII emoticons eg ":)"
					$content = preg_replace_callback(
						'/<i><u>([^ <>]+) ([^<>]+)<\/u><\/i>/i',
						$unescape_fb_emote,
						$content
					);

					//Retrieve date of the post
					$date = $post->find("abbr")[0];
					if(isset($date) && $date->hasAttribute('data-utime')){
						$date = $date->getAttribute('data-utime');
					} else {
						$date = 0;
					}

					//Build title from content
					$title = mb_substr(strip_tags($post->find('.userContent > p')[0]->innertext), 0, 20).'...';

					//Build and add final item
					$item['uri'] = self::URI . $post->find('abbr')[0]->parent()->getAttribute('href');
					$item['content'] = $content;
					$item['title'] = $title;
					$item['author'] = $author;
					$item['timestamp'] = $date;
					$this->items[] = $item;
				}
			}
		}
	}

	public function getName(){
		if(!empty($this->authorName)){
			return isset($this->extraInfos['name']) ? $this->extraInfos['name'] : $this->authorName
			. ' - Facebook Bridge';
		}

		return parent::getName();
	}
}
