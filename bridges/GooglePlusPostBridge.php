<?php
class GooglePlusPostBridge extends BridgeAbstract{

	protected $_title;
	protected $_url;

	const MAINTAINER = 'Grummfy, logmanoriginal';
	const NAME = 'Google Plus Post Bridge';
	const URI = 'https://plus.google.com/';
	const CACHE_TIMEOUT = 600; //10min
	const DESCRIPTION = 'Returns user public post (without API).';

	const PARAMETERS = array( array(
		'username' => array(
			'name' => 'username or Id',
			'required' => true
		)
	));

	public function collectData(){
		$username = $this->getInput('username');

		// Usernames start with a + if it's not an ID
		if(!is_numeric($username) && substr($username, 0, 1) !== '+') {
			$username = '+' . $username;
		}

		// get content parsed
		$html = getSimpleHTMLDOMCached(self::URI . urlencode($username) . '/posts')
			or returnServerError('No results for this query.');

		// get title, url, ... there is a lot of intresting stuff in meta
		$this->_title = $html->find('meta[property=og:title]', 0)->getAttribute('content');
		$this->_url = $html->find('meta[property=og:url]', 0)->getAttribute('content');

		// I don't even know where to start with this discusting html...
		foreach($html->find('div[jsname=WsjYwc]') as $post) {
			$item = array();

			$item['author'] = $item['fullname'] = $post->find('div div div div a', 0)->innertext;
			$item['id'] = $post->find('div div div', 0)->getAttribute('id');
			$item['avatar'] = $post->find('div img', 0)->src;
			$item['uri'] = self::URI . $post->find('div div div a', 1)->href;

			$timestamp = $post->find('a.qXj2He span', 0);

			if($timestamp) {
				$item['timestamp'] = strtotime('+' . preg_replace(
						'/[^0-9A-Za-z]/',
						'',
						$timestamp->getAttribute('aria-label')));
			}

			// hashtag to treat : https://plus.google.com/explore/tag
			// $hashtags = array();
			// foreach($post->find('a.d-s') as $hashtag){
			// 	$hashtags[trim($hashtag->plaintext)] = self::URI . $hashtag->href;
			// }

			$item['content'] = '';

			// avatar display
			$item['content'] .= '<div style="float:left; margin: 0 0.5em 0.5em 0;"><a href="'
			. self::URI
			. urlencode($this->getInput('username'));

			$item['content'] .= '"><img align="top" alt="'
			. $item['author']
			. '" src="'
			. $item['avatar']
			. '" /></a></div>';

			$content = $post->find('div[jsname=EjRJtf]', 0);
			// extract plaintext
			$item['content_simple'] = $content->plaintext;
			$item['title'] = substr($item['content_simple'], 0, 72) . '...';

			// XXX ugly but I don't have any idea how to do a better stuff,
			// str_replace on link doesn't work as expected and ask too many checks
			foreach($content->find('a') as $link) {
				$hasHttp = strpos($link->href, 'http');
				$hasDoubleSlash = strpos($link->href, '//');

				if((!$hasHttp && !$hasDoubleSlash)
				|| (false !== $hasHttp && strpos($link->href, 'http') != 0)
				|| (false === $hasHttp && false !== $hasDoubleSlash && $hasDoubleSlash != 0)) {
					// skipp bad link, for some hashtag or other stuff
					if(strpos($link->href, '/') == 0) {
						$link->href = substr($link->href, 1);
					}

					$link->href = self::URI . $link->href;
				}
			}
			$content = $content->innertext;

			$item['content'] .= '<div style="margin-top: -1.5em">' .  $content . '</div>';
			$item['content'] = trim(strip_tags($item['content'], '<a><p><div><img>'));

			$media = $post->find('[jsname="MTOxpb"]', 0);

			if($media) {

				$item['enclosures'] = array();

				foreach($media->find('img') as $img) {

					$item['enclosures'][] = $this->fixImage($img)->src;

				}

			}

			$this->items[] = $item;
		}
	}

	public function getName(){
		return $this->_title ?: 'Google Plus Post Bridge';
	}

	public function getURI(){
		return $this->_url ?: parent::getURI();
	}

	private function fixImage($img) {

		// There are certain images like .gif which link to a static picture and
		// get replaced dynamically via JS in the browser. If we want the "real"
		// image we need to account for that.

		$urlparts = parse_url($img->src);

		if(array_key_exists('host', $urlparts)) {

			// For some reason some URIs don't contain the scheme, assume https
			if(!array_key_exists('scheme', $urlparts)) {
				$urlparts['scheme'] = 'https';
			}

			$pathelements = explode('/', $urlparts['path']);

			switch($urlparts['host']) {

				case 'lh3.googleusercontent.com':

					if(pathinfo(end($pathelements), PATHINFO_EXTENSION)) {

						// The second to last element of the path specifies the
						// image format. The URL is still valid if we remove it.
						unset($pathelements[count($pathelements) - 2]);

					} elseif(strrpos(end($pathelements), '=') !== false) {

						// Some images go throug a proxy. For those images they
						// add size information after an equal sign.
						// Example: '=w530-h298-n'. Again this can safely be
						// removed to get the original image.
						$pathelements[count($pathelements) - 1] = substr(
							end($pathelements),
							0,
							strrpos(end($pathelements), '=')
						);

					}

					break;

			}

			$urlparts['path'] = implode('/', $pathelements);

		}

		echo $img->src . '<br>';

		$img->src = $this->build_url($urlparts);

		echo $img->src . '<br><br>';

		return $img;

	}

	/**
	 * From: https://gist.github.com/Ellrion/f51ba0d40ae1d62eeae44fd1adf7b704
	 * slightly adjusted to work with PHP < 7.0
	 * @param array $parts
	 * @return string
	 */
	private function build_url(array $parts)
	{

		$scheme   = isset($parts['scheme']) ? ($parts['scheme'] . '://') : '';
		$host     = isset($parts['host']) ? $parts['host'] : '';
		$port     = isset($parts['port']) ? (':' . $parts['port']) : '';
		$user     = isset($parts['user']) ? $parts['user'] : '';
		$pass     = isset($parts['pass']) ? (':' . $parts['pass']) : '';
		$pass     = ($user || $pass) ? ($pass . '@') : '';
		$path     = isset($parts['path']) ? $parts['path'] : '';
		$query    = isset($parts['query']) ? ('?' . $parts['query']) : '';
		$fragment = isset($parts['fragment']) ? ('#' . $parts['fragment']) : '';

		return implode('', [$scheme, $user, $pass, $host, $port, $path, $query, $fragment]);

	}

}
