<?php
class GooglePlusPostBridge extends BridgeAbstract{

	private $title;
	private $url;

	const MAINTAINER = 'Grummfy, logmanoriginal';
	const NAME = 'Google Plus Post Bridge';
	const URI = 'https://plus.google.com';
	const CACHE_TIMEOUT = 600; //10min
	const DESCRIPTION = 'Returns user public post (without API).';

	const PARAMETERS = array( array(
		'username' => array(
			'name' => 'username or Id',
			'required' => true
		),
		'include_media' => array(
			'name' => 'Include media',
			'type' => 'checkbox',
			'title' => 'Enable to include media in the feed content'
		)
	));

	public function collectData(){

		$username = $this->getInput('username');

		// Usernames start with a + if it's not an ID
		if(!is_numeric($username) && substr($username, 0, 1) !== '+') {
			$username = '+' . $username;
		}

		$html = getSimpleHTMLDOM(static::URI . '/' . urlencode($username) . '/posts')
			or returnServerError('No results for this query.');

		$html = defaultLinkTo($html, static::URI);

		$this->title = $html->find('meta[property=og:title]', 0)->getAttribute('content');
		$this->url   = $html->find('meta[property=og:url]', 0)->getAttribute('content');

		foreach($html->find('div[jsname=WsjYwc]') as $post) {

			$item = array();

			$item['author'] = $post->find('div div div div a', 0)->innertext;
			$item['uri'] = $post->find('div div div a', 1)->href;

			$timestamp = $post->find('a.qXj2He span', 0);

			if($timestamp) {
				$item['timestamp'] = strtotime('+' . preg_replace(
						'/[^0-9A-Za-z]/',
						'',
						$timestamp->getAttribute('aria-label')));
			}

			$message = $post->find('div[jsname=EjRJtf]', 0);

			// Empty messages are not supported right now
			if(!$message) {
				continue;
			}

			$item['content'] = '<div style="float: left; padding: 0 10px 10px 0;"><a href="'
			. $this->url
			. '"><img align="top" alt="'
			. $item['author']
			. '" src="'
			. $post->find('div img', 0)->src
			. '" /></a></div><div>'
			. trim(strip_tags($message, '<a><p><div><img>'))
			. '</div>';

			// Make title at least 50 characters long, but don't add '...' if it is shorter!
			if(strlen($message->plaintext) > 50) {
				$end = strpos($message->plaintext, ' ', 50) ?: strlen($message->plaintext);
			} else {
				$end = strlen($message->plaintext);
			}

			if(strlen(substr($message->plaintext, 0, $end)) === strlen($message->plaintext)) {
				$item['title'] = $message->plaintext;
			} else {
				$item['title'] = substr($message->plaintext, 0, $end) . '...';
			}

			$media = $post->find('[jsname="MTOxpb"]', 0);

			if($media) {

				$item['enclosures'] = array();

				foreach($media->find('img') as $img) {
					$item['enclosures'][] = $this->fixImage($img)->src;
				}

				if($this->getInput('include_media') === true && count($item['enclosures'] > 0)) {
					$item['content'] .= '<div style="clear: both;"><a href="'
					. $item['enclosures'][0]
					. '"><img src="'
					. $item['enclosures'][0]
					. '" /></a></div>';
				}

			}

			// Add custom parameters (only useful for JSON or Plaintext)
			$item['fullname'] = $item['author'];
			$item['avatar'] = $post->find('div img', 0)->src;
			$item['id'] = $post->find('div div div', 0)->getAttribute('id');
			$item['content_simple'] = $message->plaintext;

			$this->items[] = $item;

		}

	}

	public function getName(){
		return $this->title ?: 'Google Plus Post Bridge';
	}

	public function getURI(){
		return $this->url ?: parent::getURI();
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

		$img->src = $this->build_url($urlparts);
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
