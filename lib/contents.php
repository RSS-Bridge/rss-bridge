<?php
function getContents($url, $header = array(), $opts = array()){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

	if(is_array($header) && count($header) !== 0)
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

	curl_setopt($ch, CURLOPT_USERAGENT, ini_get('user_agent'));
	curl_setopt($ch, CURLOPT_ENCODING, '');
	curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

	if(is_array($opts)) {
		foreach($opts as $key => $value) {
			curl_setopt($ch, $key, $value);
		}
	}

	if(defined('PROXY_URL') && !defined('NOPROXY')) {
		curl_setopt($ch, CURLOPT_PROXY, PROXY_URL);
	}

	// We always want the resonse header as part of the data!
	curl_setopt($ch, CURLOPT_HEADER, true);

	$data = curl_exec($ch);
	$curlError = curl_error($ch);
	$curlErrno = curl_errno($ch);

	if($data === false)
		debugMessage('Cant\'t download ' . $url . ' cUrl error: ' . $curlError . ' (' . $curlErrno . ')');

	$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header = substr($data, 0, $headerSize);
	$headers = parseResponseHeader($header);
	$finalHeader = end($headers);

	error_log(json_encode($headers));

	if(array_key_exists('http_code', $finalHeader)
	&& strpos($finalHeader['http_code'], '200') === false
	&& array_key_exists('Server', $finalHeader)
	&& strpos($finalHeader['Server'], 'cloudflare') !== false) {
		returnServerError(<<< EOD
The server responded with a Cloudflare challenge, which is not supported by RSS-Bridge!<br>
If this error persists longer than a week, please consider opening an issue on GitHub!
EOD
		);
	}

	curl_close($ch);
	return substr($data, $headerSize);
}

function getSimpleHTMLDOM($url,
$header = array(),
$opts = array(),
$lowercase = true,
$forceTagsClosed = true,
$target_charset = DEFAULT_TARGET_CHARSET,
$stripRN = true,
$defaultBRText = DEFAULT_BR_TEXT,
$defaultSpanText = DEFAULT_SPAN_TEXT){
	$content = getContents($url, $header, $opts);
	return str_get_html($content,
	$lowercase,
	$forceTagsClosed,
	$target_charset,
	$stripRN,
	$defaultBRText,
	$defaultSpanText);
}

/**
 * Maintain locally cached versions of pages to avoid multiple downloads.
 * @param url url to cache
 * @param duration duration of the cache file in seconds (default: 24h/86400s)
 * @return content of the file as string
 */
function getSimpleHTMLDOMCached($url,
$duration = 86400,
$header = array(),
$opts = array(),
$lowercase = true,
$forceTagsClosed = true,
$target_charset = DEFAULT_TARGET_CHARSET,
$stripRN = true,
$defaultBRText = DEFAULT_BR_TEXT,
$defaultSpanText = DEFAULT_SPAN_TEXT){
	debugMessage('Caching url ' . $url . ', duration ' . $duration);

	// Initialize cache
	$cache = Cache::create('FileCache');
	$cache->setPath(CACHE_DIR . '/pages');
	$cache->purgeCache(86400); // 24 hours (forced)

	$params = [$url];
	$cache->setParameters($params);

	// Determine if cached file is within duration
	$time = $cache->getTime();
	if($time !== false
	&& (time() - $duration < $time)
	&& (!defined('DEBUG') || DEBUG !== true)) { // Contents within duration
		$content = $cache->loadData();
	} else { // Content not within duration
		$content = getContents($url, $header, $opts);
		if($content !== false) {
			$cache->saveData($content);
		}
	}

	return str_get_html($content,
	$lowercase,
	$forceTagsClosed,
	$target_charset,
	$stripRN,
	$defaultBRText,
	$defaultSpanText);
}

/**
 * Parses the provided response header into an associative array
 *
 * Based on https://stackoverflow.com/a/18682872
 */
function parseResponseHeader($header) {

	$headers = array();
	$requests = explode("\r\n\r\n", trim($header));

	foreach ($requests as $request) {

		$header = array();

		foreach (explode("\r\n", $request) as $i => $line) {

			if($i === 0) {
				$header['http_code'] = $line;
			} else {

				list ($key, $value) = explode(': ', $line);
				$header[$key] = $value;

			}

		}

		$headers[] = $header;

	}

	return $headers;

}
