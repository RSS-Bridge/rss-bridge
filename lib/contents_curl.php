<?php
function curlgetContents( $url, $params, $post=false){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $post ? $url : $url.'?'.http_build_query($params) );
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/rssbridge-fb-cookies.txt');
	curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/rssbridge-fb-cookies.txt');

	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLINFO_HEADER_OUT, 1);

	if ( $post ) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/x-www-form-urlencoded',
			'User-Agent: '.ini_get('user_agent'),
		));
	} else {
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'User-Agent: '.ini_get('user_agent'),
		));
	}

	$response = curl_exec($ch);
	$info = curl_getinfo($ch);

	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$header = substr($response, 0, $header_size);
	$body = substr($response, $header_size);
	curl_close($ch);
	file_put_contents(__DIR__.'/../debug/D'.date('H-i-s').'.html', $body);

	return array($body, $info, $header);

}
function curlgetSimpleHTMLDOM($url
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
	list($body, $info, $header) = curlgetContents($url, $use_include_path, $context, $offset, $maxLen);
	return array(str_get_html($body
		, $lowercase
		, $forceTagsClosed
		, $target_charset
		, $stripRN
		, $defaultBRText
		, $defaultSpanText),
	$info, $header);
}
?>
