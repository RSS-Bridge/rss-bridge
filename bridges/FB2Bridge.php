<?php
class FB2Bridge extends BridgeAbstract {

	const MAINTAINER = 'teromene';
	const NAME = 'Facebook Alternate';
	const URI = 'https://www.facebook.com/';
	const CACHE_TIMEOUT = 1000;
	const DESCRIPTION = 'Input a page title or a profile log. For a profile log,
 please insert the parameter as follow : myExamplePage/132621766841117';

	const PARAMETERS = array( array(
		'u' => array(
			'name' => 'Username',
			'required' => true
		)
	));

	public function collectData(){

		//Utility function for cleaning a Facebook link
		$unescape_fb_link = function($matches){
			if(is_array($matches) && count($matches) > 1) {
				$link = $matches[1];
				if(strpos($link, '/') === 0)
					$link = self::URI . substr($link, 1);
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

		if($this->getInput('u') !== null) {
			$page = 'https://touch.facebook.com/' . $this->getInput('u');
			$cookies = $this->getCookies($page);
			$pageID = $this->getPageID($page, $cookies);

			if($pageID === null) {
				echo <<<EOD
Unable to get the page id. You should consider getting the ID by hand, then importing it into FB2Bridge
EOD;
				die();
			} elseif($pageID == -1) {
				echo <<<EOD
This page is not accessible without being logged in.
EOD;
				die();
			}
		}

		//Build the string for the first request
		$requestString = 'https://touch.facebook.com/pages_reaction_units/more/?page_id='
		. $pageID
		. '&cursor={"card_id"%3A"videos"%2C"has_next_page"%3Atrue}&surface=mobile_page_home&unit_count=8';

		$fileContent = getContents($requestString);

		$articleIndex = 0;
		$maxArticle = 3;

		$html = $this->buildContent($fileContent);
		$author = $this->getInput('u');

		foreach($html->find('article') as $content) {

			$item = array();

			$item['uri'] = 'http://touch.facebook.com'
			. $content->find("div[class='_52jc _5qc4 _24u0 _36xo']", 0)->find('a', 0)->getAttribute('href');

			if($content->find('header', 0) !== null) {
				$content->find('header', 0)->innertext = '';
			}

			if($content->find('footer', 0) !== null) {
				$content->find('footer', 0)->innertext = '';
			}

			//Remove html nodes, keep only img, links, basic formatting
			$content = strip_tags($content, '<a><img><i><u><br><p><h3><h4>');

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
			// "<i><u>smile emoticon</u></i>" back to ASCII emoticons eg ":)"
			$content = preg_replace_callback('/<i><u>([^ <>]+) ([^<>]+)<\/u><\/i>/i', $unescape_fb_emote, $content);

			$item['content'] = $content;

			$title = $author;
			if (strlen($title) > 24)
				$title = substr($title, 0, strpos(wordwrap($title, 24), "\n")) . '...';
			$title = $title . ' | ' . strip_tags($content);
			if (strlen($title) > 64)
				$title = substr($title, 0, strpos(wordwrap($title, 64), "\n")) . '...';

			$item['title'] = $title;
			$item['author'] = $author;

			array_push($this->items, $item);
		}
	}


	// Currently not used. Is used to get more than only 3 elements, as they appear on another page.
	private function computeNextLink($string, $pageID){

		$regex = implode(
			'',
			array(
				'/timeline_unit',
				"\\\\\\\\u00253A1",
				"\\\\\\\\u00253A([0-9]*)",
				"\\\\\\\\u00253A([0-9]*)",
				"\\\\\\\\u00253A([0-9]*)",
				"\\\\\\\\u00253A([0-9]*)/"
			)
		);

		preg_match($regex, $string, $result);

		return implode(
			'',
			array(
				'https://touch.facebook.com/pages_reaction_units/more/?page_id=',
				$pageID,
				'&cursor=%7B%22timeline_cursor%22%3A%22timeline_unit%3A1%3A',
				$result[1],
				'%3A',
				$result[2],
				'%3A',
				$result[3],
				'%3A',
				$result[4],
				'%22%2C%22timeline_section_cursor%22%3A%7B%7D%2C%22',
				'has_next_page%22%3Atrue%7D&surface=mobile_page_home&unit_count=3'
			)
		);
	}

	//Builds the HTML from the encoded JS that Facebook provides.
	private function buildContent($pageContent){
		// The html ends with:
		// /div>","replaceifexists
		$regex = '/\\"html\\":(\".+\/div>"),"replace/';
		preg_match($regex, $pageContent, $result);
		return str_get_html(html_entity_decode(json_decode($result[1])));
	}


	//Builds the cookie from the page, as Facebook sometimes refuses to give
	//the page if no cookie is provided.
	private function getCookies($pageURL){

		$ctx = stream_context_create(array(
			'http' => array(
				'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0',
				'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
				)
			)
		);
		$a = file_get_contents($pageURL, 0, $ctx);

		//First request to get the cookie
		$cookies = '';
		foreach($http_response_header as $hdr) {
			if(strpos($hdr, 'Set-Cookie') !== false) {
				$cLine = explode(':', $hdr)[1];
				$cLine = explode(';', $cLine)[0];
				$cookies .= ';' . $cLine;
			}
		}

		return substr($cookies, 1);
	}

	//Get the page ID from the Facebook page.
	private function getPageID($page, $cookies){

		$context = stream_context_create(array(
			'http' => array(
				'user_agent' => 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:46.0) Gecko/20100101 Firefox/46.0',
				'header' => 'Cookie: ' . $cookies
				)
			)
		);

		$pageContent = file_get_contents($page, 0, $context);

		if(strpos($pageContent, 'signup-button') != false) {
			return -1;
		}

		//Get the page ID if we don't have a captcha
		$regex = '/page_id=([0-9]*)&/';
		preg_match($regex, $pageContent, $matches);

		if(count($matches) > 0) {
			return $matches[1];
		}

		//Get the page ID if we do have a captcha
		$regex = '/"pageID":"([0-9]*)"/';
		preg_match($regex, $pageContent, $matches);

		return $matches[1];

	}

	public function getName(){
		return (isset($this->name) ? $this->name . ' - ' : '') . 'Facebook Bridge';
	}

	public function getURI(){
		return 'http://facebook.com';
	}

}
