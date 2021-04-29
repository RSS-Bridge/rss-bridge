<?php
class FurAffinityUserBridge extends BridgeAbstract {
	const NAME = 'FurAffinity User Gallery';
	const URI = 'https://www.furaffinity.net';
	const MAINTAINER = 'CyberJacob';
	const PARAMETERS = array(
		array(
			'searchUsername' => array(
				'name' => 'Search Username',
				'type' => 'text',
				'required' => true,
				'title' => 'Username to fetch the gallery for'
			),
			'loginUsername' => array(
				'name' => 'Login Username',
				'type' => 'text',
				'required' => true
			),
			'loginPassword' => array(
				'name' => 'Login Password',
				'type' => 'text',
				'required' => true
			)
		)
	);

	public function collectData() {
		$cookies = self::login();
		$url = self::URI . '/gallery/' . $this->getInput('searchUsername');

		$html = getSimpleHTMLDOM($url, $cookies)
			or returnServerError('Could not load the user\'s galary page.');

		$submissions = $html->find('section[id=gallery-gallery]', 0)->find('figure');
		foreach($submissions as $submission) {
			$item = array();
			$item['title'] = $submission->find('figcaption', 0)->find('a', 0)->plaintext;

			$thumbnail = $submission->find('a', 0);
			$thumbnail->href = self::URI . $thumbnail->href;

			$item['content'] = $submission->find('a', 0);

			$this->items[] = $item;
		}
	}

	public function getName() {
		return self::NAME . ' for ' . $this->getInput('searchUsername');
	}

	public function getURI() {
		return self::URI . '/user/' . $this->getInput('searchUsername');
	}

	private function login() {
		$ch = curl_init(self::URI . '/login/');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt($ch, CURLOPT_USERAGENT, ini_get('user_agent'));
		curl_setopt($ch, CURLOPT_ENCODING, '');
		curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

		$fields = implode('&', array(
			'action=login',
			'retard_protection=1',
			'name=' . urlencode($this->getInput('loginUsername')),
			'pass=' . urlencode($this->getInput('loginPassword')),
			'login=Login to Faraffinity'
		));

		curl_setopt($ch, CURLOPT_POST, 5);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

		if(defined('PROXY_URL') && !defined('NOPROXY')) {
			curl_setopt($ch, CURLOPT_PROXY, PROXY_URL);
		}

		curl_setopt($ch, CURLOPT_HEADER, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		$data = curl_exec($ch);

		$errorCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$curlError = curl_error($ch);
		$curlErrno = curl_errno($ch);
		$curlInfo = curl_getinfo($ch);

		if($data === false)
			fDebug::log("Cant't download {$url} cUrl error: {$curlError} ({$curlErrno})");

		curl_close($ch);

		if($errorCode != 200) {
			returnServerError(error_get_last());
		} else {
			preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $data, $matches);
			$cookies = array();

			foreach($matches[1] as $item) {
				parse_str($item, $cookie);
				$cookies = array_merge($cookies, $cookie);
			}

			return $cookies;
		}
	}
}
