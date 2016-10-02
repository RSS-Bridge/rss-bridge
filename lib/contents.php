<?php
function getContents($url
, $use_include_path = false
, $context = null
, $offset = 0
, $maxlen = null
){
	$contextOptions = array(
		'http' => array(
			'user_agent' => ini_get('user_agent')
		)
	);

	if(defined('PROXY_URL') && !defined('NOPROXY')){
		$contextOptions['http']['proxy'] = PROXY_URL;
		$contextOptions['http']['request_fulluri'] = true;

		if(is_null($context)){
			$context = stream_context_create($contextOptions);
		} else {
			$prevContext = $context;
			if(!stream_context_set_option($context, $contextOptions)){
				$context = $prevContext;
			}
		}
	}

	if(is_null($maxlen)){
		$content = @file_get_contents($url, $use_include_path, $context, $offset);
	} else {
		$content = @file_get_contents($url, $use_include_path, $context, $offset, $maxlen);
	}

	if($content === false)
		debugMessage('Cant\'t download ' . $url);

	// handle compressed data
	foreach($http_response_header as $header){
		if(stristr($header, 'content-encoding')){
			switch(true){
			case stristr($header, 'gzip'):
				$content = gzinflate(substr($content, 10, -8));
				break;
			case stristr($header, 'compress'):
				//TODO
			case stristr($header, 'deflate'):
				//TODO
			case stristr($header, 'brotli'):
				//TODO
				returnServerError($header . '=> Not implemented yet');
				break;
			case stristr($header, 'identity'):
				break;
			default:
				returnServerError($header . '=> Unknown compression');
			}
		}
	}

	return $content;
}

function getSimpleHTMLDOM($url
	, $use_include_path = false
	, $context = null
	, $offset = 0
	, $maxLen = null
	, $lowercase = true
	, $forceTagsClosed = true
	, $target_charset = DEFAULT_TARGET_CHARSET
	, $stripRN = true
	, $defaultBRText = DEFAULT_BR_TEXT
	, $defaultSpanText = DEFAULT_SPAN_TEXT
){
	$content = getContents($url, $use_include_path, $context, $offset, $maxLen);
	return str_get_html($content
		, $lowercase
		, $forceTagsClosed
		, $target_charset
		, $stripRN
		, $defaultBRText
		, $defaultSpanText);
}

/**
 * Maintain locally cached versions of pages to avoid multiple downloads.
 * @param url url to cache
 * @param duration duration of the cache file in seconds (default: 24h/86400s)
 * @return content of the file as string
 */
function getSimpleHTMLDOMCached($url
	, $duration = 86400
	, $use_include_path = false
	, $context = null
	, $offset = 0
	, $maxLen = null
	, $lowercase = true
	, $forceTagsClosed = true
	, $target_charset = DEFAULT_TARGET_CHARSET
	, $stripRN = true
	, $defaultBRText = DEFAULT_BR_TEXT
	, $defaultSpanText = DEFAULT_SPAN_TEXT
){
	debugMessage('Caching url ' . $url . ', duration ' . $duration);

	$filepath = __DIR__ . '/../cache/pages/' . sha1($url) . '.cache';
	debugMessage('Cache file ' . $filepath);

	if(file_exists($filepath) && filectime($filepath) < time() - $duration){
		unlink ($filepath);
		debugMessage('Cached file deleted: ' . $filepath);
	}

	if(file_exists($filepath)){
		debugMessage('Loading cached file ' . $filepath);
		touch($filepath);
		$content = file_get_contents($filepath);
	} else {
		debugMessage('Caching ' . $url . ' to ' . $filepath);
		$dir = substr($filepath, 0, strrpos($filepath, '/'));

		if(!is_dir($dir)){
			debugMessage('Creating directory ' . $dir);
			mkdir($dir, 0777, true);
		}

		$content = getContents($url, $use_include_path, $context, $offset, $maxLen);
		if($content !== false){
			file_put_contents($filepath, $content);
		}
	}

	return str_get_html($content
		, $lowercase
		, $forceTagsClosed
		, $target_charset
		, $stripRN
		, $defaultBRText
		, $defaultSpanText);
}

?>
