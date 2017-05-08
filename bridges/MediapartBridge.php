<?php
/**
* MediapartBridge
* Get the full content of articles (w/ username & password).
* TODO : 2- surcharge constructor for replacing registerSession
* TODO : 3- encryption / obfuscation of password
*
* SECURITY CAUTION. This service is insecure:
*	- Password field in RSS-Bridge Front page (viewer discretion is required)
*	- you have to store your password in your RSS-client (in the feed URL)
*	- RSS-client to RSS-bridge transmission is unsecure (unless you have HTTPS enabled)
*	- cookie-storage (stealable) contain plaintext for user and session cookie (not password)
*	- cURL & simple_html_dom are not configured to check SSL/TLS viability (& trustness)
*
* @name Mediapart
* @homepage https://www.mediapart.fr/
* @description Returns full articles from Mediapart newspaper.
* @maintainer GregThib
* @use1(user="user", pass="password")
*/
class MediapartBridge extends BridgeAbstract{
	
	// maximum articles to fetch by a unique call
	const FETCH_LIMIT = 5;
	
	// file for storing cookies
	const COOKIE_FILE = 'cache/MediapartBridge.cookies.json';
	
	// random value and algorithm for password obfuscation
	const OBFUSCATION = '52ce5cbbbda1dc6c78f0';
	const OBFUSC_ALGO = 'ripemd160';
	
	// Maximum number of failback 'http ' to 'https' in the FOLLOW REDIRECT
	const BOUND_LIMIT = 3;
	
	
	private function StripCDATA($string) {
		$string = str_replace('<![CDATA[', '', $string);
		$string = str_replace(']]>',       '', $string);
		return $string;
	}
	
	private function obfuscateCreds($user,$pass) {
		return hash(self::OBFUSC_ALGO,$user.":".self::OBFUSCATION.$pass);
	}
	
	private function submitAuthForm(&$html, $user, $pass) {
		// new cookie
		$cookie = new stdClass();
		
		// get auth form and fill it!
		$auth   = $html->find('form[id=logFormEl]', 0) or $this->returnError('Form has changed...', 422);
		$action = str_replace('http://','https://',$auth->action);
		$post_data['name']          = $user;
		$post_data['pass']          = $pass;
		$post_data['op']            = $auth->find('input[name=op]', 0)->value;
		$post_data['form_build_id'] = $auth->find('input[name=form_build_id]', 0)->value;
		$post_data['form_id']       = $auth->find('input[name=form_id]', 0)->value;
		
		// anonymous function with heritage to parse header !
		$httplocation = '';
		$parse_header = function($ch, $headline) use(&$cookie, &$httplocation) {
			// WARN !!!! The first preg_match will not work in case of cookie with no expires="" parameters !
			// 03:38 am : _Please_, if you read, understand this AND your are a regex Jedi, correct this !
			//		--> REWARD : a beer / my body
			if(preg_match('/^Set-Cookie:\s*([^;]+).*(?:;\s*expires=([^;]*)).*$/i', $headline, $matches)) {
				// case: cookie w/ expires (baouuuuuuhh!)
				$cookie->settext .= ($cookie->settext ? '; ' : 'Cookie: ').$matches[1];
				if(preg_match('/^SESS.*$/i', $matches[1]))
					$cookie->expires = $matches[2];
			} elseif(preg_match('/^Location:\s*([^\r]*)\r?$/i', $headline, $matches))
				// case: location
				$httplocation = str_replace('http://','https://',$matches[1]);
			return strlen($headline);
		};
		
		// form submission with curl
		$ch = curl_init($action);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_REDIR_PROTOCOLS, CURLPROTO_HTTPS);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, $parse_header);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_COOKIESESSION,true);
		curl_setopt($ch, CURLOPT_COOKIEFILE,"");
		
		// simulated follow redirect (w/ not HTTPS forbidden)
		$failback = 0;
		while(!($html = curl_exec($ch)) && $failback++ < self::BOUND_LIMIT) {
			if($failback++ == BOUND_LIMIT)
				$this->returnError('Probable infinite loop in redirections',500);
			elseif(curl_errno($ch) == 1) // failure : not HTTPS follow value
				curl_setopt($ch, CURLOPT_URL, $httplocation);
			else
				$this->returnError('Submission failed w/ curl_error ('.curl_errno($ch).') =\"'.curl_error($ch).'"',500);
		}
		curl_close($ch);
		
		// prepare html & session cookie for simple_html_dom
		$html  = str_get_html($html);
		$creds = $this->obfuscateCreds($user,$pass);
		return $this->getSessionToken($creds,$cookie);
	}
	
	private function getSessionToken($creds, $cookie= NULL) {
		// load cookies
		if (file_exists(self::COOKIE_FILE)) {
			$file_content = file_get_contents(self::COOKIE_FILE);
			$cookies = (array)json_decode($file_content);
		} else
			$cookies = array();
		
		// in "get" mode: clean, else, save new one if not expired, else do nothing
		$save = true;
		if(!isset($cookie)) {
			$save = false;
			foreach($cookies as $key => $value) {
				if($value->expires < time() - 180) {
					unset($cookies[$key]);
					$save = true;
				}
			}
		}
		elseif(isset($cookie->settext) && isset($cookie->expires) && $cookie->expires > time())
			$cookies[$creds] = $cookie;
		else
			$save = false;
		
		// write in file
		if($save) file_put_contents(self::COOKIE_FILE, json_encode($cookies));
		
		// session context (for simple_html_dom)
		return stream_context_create(
			array('http' => array_key_exists($creds,$cookies) ?
				array('header' => $cookies[$creds]->settext) :
				array()
		));
	}
	
	private function ExtractContent($url, &$session, $user, $pass) {
		$html= '';
		
		// fetch full content
		$html = file_get_html($url.'?onglet=full', false, $session) or $this->returnError('Error during fetch_full_content', 500);
		
		// if not connected, try to log on
		if($html->find('form[id=logFormEl]', 0) && !($session = $this->submitAuthForm($html, $user, $pass)))
			$this->returnError('Credentials didn\'t works!', 404);
		
		// 01 - deletion of "à lire aussi"
		$lireaussi = $html->find('div.content-article div[id=lire-aussi]');
		foreach($lireaussi as $bloc) $bloc->outertext = '';
		
		// end of manipulations
		$html->load($html->save());
		
		// compound recup and recomposition
		$head = $html->find('div.chapo div.clear-block', 0)->innertext;
		$text = $html->find('div.content-article', 0)->innertext or $this->returnError('Content not found on article', 404);
		return '<b>'.$head.'</b>'.$text;
	}

	public function collectData(array $param) {
		// check params
		if (!isset($param['user']) || !isset($param['pass']))
			$this->returnError('You must specify your credentials', 400);
		
		// get session token
		$creds   = $this->obfuscateCreds($param['user'],$param['pass']);
		$session = $this->getSessionToken($creds);
		
		// get Mediapart feed
		$html = file_get_html('https://www.mediapart.fr/articles/feed') or $this->returnError('Could not request Mediapart.', 404);
		
		// fetch items
		$limit = 0;
		foreach($html->find('item') as $element) {
			if($limit++ < self::FETCH_LIMIT) {
				$item = new \Item();
				$item->title     = $this->StripCDATA($element->find('title', 0)->innertext);
				$item->title     = str_replace(['\''],['\\\''],$item->title);
				$item->name      = $this->StripCDATA($element->find('dc:creator', 0)->innertext);
				$item->uri       = $this->StripCDATA($element->find('comments', 0)->plaintext);
				$item->uri       = str_replace(['http://','#comments'], ['https://',''], $item->uri);
				$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
				$item->content   = $this->ExtractContent($item->uri, $session, $param['user'],$param['pass']);
				$this->items[]   = $item;
			}
		}
	}

	public function getName(){
		return 'Mediapart';
	}

	public function getURI(){
		return 'https://www.mediapart.fr';
	}

	public function getCacheDuration(){
		return 3600; // 1 hour
	}
}
