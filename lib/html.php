<?php
function sanitize($textToSanitize,
$removedTags = array('script', 'iframe', 'input', 'form'),
$keptAttributes = array('title', 'href', 'src'),
$keptText = array()){
	$htmlContent = str_get_html($textToSanitize);

	foreach($htmlContent->find('*[!b38fd2b1fe7f4747d6b1c1254ccd055e]') as $element) {
		if(in_array($element->tag, $keptText)) {
			$element->outertext = $element->plaintext;
		} elseif(in_array($element->tag, $removedTags)) {
			$element->outertext = '';
		} else {
			foreach($element->getAllAttributes() as $attributeName => $attribute) {
				if(!in_array($attributeName, $keptAttributes))
					$element->removeAttribute($attributeName);
			}
		}
	}

	return $htmlContent;
}

function backgroundToImg($htmlContent) {

	$regex = '/background-image[ ]{0,}:[ ]{0,}url\([\'"]{0,}(.*?)[\'"]{0,}\)/';
	$htmlContent = str_get_html($htmlContent);

	foreach($htmlContent->find('*[!b38fd2b1fe7f4747d6b1c1254ccd055e]') as $element) {

		if(preg_match($regex, $element->style, $matches) > 0) {

			$element->outertext = '<img style="display:block;" src="' . $matches[1] . '" />';

		}

	}

	return $htmlContent;

}

function defaultLinkTo($content, $server){
	foreach($content->find('img') as $image) {
		if(strpos($image->src, 'http') === false
		&& strpos($image->src, '//') === false
		&& strpos($image->src, 'data:') === false)
			$image->src = $server . $image->src;
	}

	foreach($content->find('a') as $anchor) {
		if(strpos($anchor->href, 'http') === false
		&& strpos($anchor->href, '//') === false
		&& strpos($anchor->href, '#') !== 0
		&& strpos($anchor->href, '?') !== 0)
			$anchor->href = $server . $anchor->href;
	}

	return $content;
}

/*

A spiritual port of Python's urlparse.urljoin() function to PHP.

Author: fluffy, http://beesbuzz.biz/
Latest version at: https://github.com/plaidfluff/php-urljoin

 */

function urljoin($base, $rel) {
	$pbase = parse_url($base);
	$prel = parse_url($rel);

	$merged = array_merge($pbase, $prel);
	if ($prel['path'][0] != '/') {
		// Relative path
		$dir = preg_replace('@/[^/]*$@', '', $pbase['path']);
		$merged['path'] = $dir . '/' . $prel['path'];
	}

	// Get the path components, and remove the initial empty one
	$pathParts = explode('/', $merged['path']);
	array_shift($pathParts);

	$path = [];
	$prevPart = '';
	foreach ($pathParts as $part) {
		if ($part == '..' && count($path) > 0) {
			// Cancel out the parent directory (if there's a parent to cancel)
			$parent = array_pop($path);
			// But if it was also a parent directory, leave it in
			if ($parent == '..') {
				array_push($path, $parent);
				array_push($path, $part);
			}
		} else if ($prevPart != '' || ($part != '.' && $part != '')) {
			// Don't include empty or current-directory components
			if ($part == '.') {
				$part = '';
			}
			array_push($path, $part);
		}
		$prevPart = $part;
	}
	$merged['path'] = '/' . implode('/', $path);

	$ret = '';
	if (isset($merged['scheme'])) {
		$ret .= $merged['scheme'] . ':';
	}

	if (isset($merged['scheme']) || isset($merged['host'])) {
		$ret .= '//';
	}

	if (isset($prel['host'])) {
		$hostSource = $prel;
	} else {
		$hostSource = $pbase;
	}

	// username, password, and port are associated with the hostname, not merged
	if (isset($hostSource['host'])) {
		if (isset($hostSource['user'])) {
			$ret .= $hostSource['user'];
			if (isset($hostSource['pass'])) {
				$ret .= ':' . $hostSource['pass'];
			}
			$ret .= '@';
		}
		$ret .= $hostSource['host'];
		if (isset($hostSource['port'])) {
			$ret .= ':' . $hostSource['port'];
		}
	}

	if (isset($merged['path'])) {
		$ret .= $merged['path'];
	}

	if (isset($prel['query'])) {
		$ret .= '?' . $prel['query'];
	}

	if (isset($prel['fragment'])) {
		$ret .= '#' . $prel['fragment'];
	}

	return $ret;
}
