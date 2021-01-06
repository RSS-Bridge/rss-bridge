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

function cleanEmojis($body) {
	foreach($body->find('img.emoji') as $emojiElem) {
		assertc(!empty($emojiElem->getAttribute('alt')), 'extractContent() failed to extract emojis');
		$emoji = $emojiElem->getAttribute('alt');
		$emojiElem->outertext = $emoji;
	}
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
	return has($elem, $selector) && !empty(trim($elem->find($selector)[0]->plaintext));
}

function hasAttr($elem, $attr, $selector = false) {
	if($selector === false) {
		return !empty(trim($elem->getAttribute($attr)));
	} else {
		return !empty(trim($elem->find($selector)[0]->getAttribute($attr)));
	}
}

function assertc($condition, $message) {
	if(!$condition) {
		throw new \Exception($message);
	}
}

?>
