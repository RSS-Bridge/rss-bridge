<?php
namespace d7sd6u\VKPostsExtractorParser;

function getPostUrlFromId($postId) {
	return 'https://vk.com/wall' . $postId;
}

function getMobilePostUrlFromId($postId) {
	return 'https://m.vk.com/wall' . $postId;
}

function getPostIdFromUrl($postUrl) {
	preg_match('/wall(-?\d+_\d+)/', $postUrl, $matches);
	return $matches[1];
}

function getFileIdFromUrl($nativeFileUrl) {
	preg_match('/doc(-?\d+_-?\d+)(\?.*)?/', $nativeFileUrl, $matches);
	assertc(isset($matches[1]), 'getFileByUrl() failed to extract file from url: ' . $nativeFileUrl);
	return $matches[1];
}

function getFileDirectUrlById($fileId) {
	$fileUrl = 'https://m.vk.com/doc' . $fileId; // mobile version always redirects to direct url, so that is good enough for now
	return $fileUrl;
}

function extractBackgroundImage($elem) {
	preg_match('/background(-image)?: url\((.+?)\)/', $elem->getAttribute('style'), $matches);
	assertc(isset($matches[2]), 'extractBackgroundImage() failed to extract image from element"');
	return $matches[2];
}

function has($elem, ...$selectors) {
	$cond = true;
	foreach($selectors as $selector) {
		$cond = $cond && count($elem->find($selector)) != 0;
	}
	return $cond;
}

// checks if any descendent of element matches selector and has not empty plaintext
function check($elem, $selector) {
	return has($elem, $selector) && !empty(trim($elem->first($selector)->text()));
}

function cleanUrls($dom) {
	foreach($dom->findInDocument('a') as $link) {
		// check if url in link is redirect, i.e. /away.php?to=<canonical url>&<some vk's parameters>
		// first subexpression is canonical url: all chars after "/away.php?to=", except other possible parameters in "dirty" url
		if(preg_match('#^/away.php\?to=(.*?)(&.*)*$#', $link->getAttribute('href'), $matches)) {
			$clean_url = $matches[1];
			$link->setAttribute('href', urldecode($clean_url));
		}
		// check if url is relative
		if(preg_match('#^/.*$#', $link->getAttribute('href'), $matches)) {
			$clean_url = 'https://vk.com' . $matches[0];
			$link->setAttribute('href', $clean_url);
		}
	}
	return $dom;
}

function hasAttr($elem, $attr, $selector = false) {
	if($selector === false) {
		return !empty(trim($elem->getAttribute($attr)));
	} else {
		return !empty(trim($elem->first($selector)->getAttribute($attr)));
	}
}

function assertc($condition, $message) {
	if(!$condition) {
		throw new \Exception($message);
	}
}

?>
