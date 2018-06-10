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

	$content = curl_exec($ch);
	$curlError = curl_error($ch);
	$curlErrno = curl_errno($ch);
	curl_close($ch);

	if($content === false)
		debugMessage('Cant\'t download ' . $url . ' cUrl error: ' . $curlError . ' (' . $curlErrno . ')');

	return $content;
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
