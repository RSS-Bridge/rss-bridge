<?php
class mruac_PatreonUserBridge extends PatreonBridge
{
	const NAME = 'Authenticated Patreon Bridge';
	const DESCRIPTION = 'Patreon Bridge for patron-only Patreon pages';
	const MAINTAINER = 'mruac';
	const CONFIGURATION = [
		'session_id' => [
			'required' => true
		]
	];

	//override apiGet() with cookie
	protected function apiGet($endpoint, $query_data = [])
	{
		$query_data['json-api-version'] = 1.0;
		$query_data['json-api-use-default-includes'] = 0;

		$url = 'https://www.patreon.com/api/'
			. $endpoint
			. '?'
			. http_build_query($query_data);

		/*
         * Accept-Language header and the CURL cipher list are for bypassing the
         * Cloudflare anti-bot protection on the Patreon API. If this ever breaks,
         * here are some other project that also deal with this:
         * https://github.com/mikf/gallery-dl/issues/342
         * https://github.com/daemionfox/patreon-feed/issues/7
         * https://www.patreondevelopers.com/t/api-returning-cloudflare-challenge/2025
         * https://github.com/splitbrain/patreon-rss/issues/4
         */
		$header = [
			'Accept-Language: en-US',
			'Content-Type: application/json'
		];
		$opts = [
			CURLOPT_COOKIE => 'session_id=' . $this->getCookie(),
			CURLOPT_SSL_CIPHER_LIST => implode(':', [
				'DEFAULT',
				'!DHE-RSA-CHACHA20-POLY1305'
			])
		];

		try {
			$data = getContents($url, $header, $opts, true);
			$this->checkCookie($data);
			return json_decode($data['content']);
		} catch (HttpException $e) {
			if ($e->getCode() === 401) {
				$this->saveCacheValue('cookie',  null); //clear cache
				$this->saveCacheValue('is_authenticated',  false);
				returnServerError('Cookie not authenticated. Check your 🍪!');
			} else {
				returnServerError('apiGet(' . $url . ') failed with HTTP code: ' . $e->getCode());
			}
		}
	}

	private function checkCookie($data)
	{
		//check cookie to see if it is authenticated
		$value = $this->loadCacheValue('is_authenticated', 31622400 /* 365 + 1 days to let cookie chance to renew */);
		if (!isset($value) || !$value) {
			$full_name = json_decode($data['content'])->data->attributes->full_name ?? null;
			if (isset($full_name)) {
				$this->saveCacheValue('is_authenticated',  true);
			} else {
				$this->apiGet('current_user');
			}
		}

		if (array_key_exists('set-cookie', $data['headers'])) {
			foreach ($data['headers']['set-cookie'] as $key => $value) {
				if (str_starts_with('session_id=', $value)) {
					parse_str(strtr($value, ['&' => '%26', '+' => '%2B', ';' => '&']), $cookie);
					if ($cookie['session_id'] != $this->getCookie()) {
						$this->updateCookie($cookie['session_id']);
					}
					break;
				}
			}
		}
	}

	private function updateCookie(string $cookie_str)
	{
		$this->saveCacheValue('cookie',  $cookie_str); //resave to renew the cache
		return $cookie_str;
	}

	private function getCookie()
	{
		// checks if cookie is set, if not initialise it with the cookie from the config
		$value = $this->loadCacheValue('cookie', 31622400 /* 365 + 1 days to let cookie chance to renew */);
		if (!isset($value)) {
			$value = $this->updateCookie($this->getOption('session_id'));
		}
		return $value;
	}
}
