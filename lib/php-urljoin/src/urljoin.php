<?php

/*

A spiritual port of Python's urlparse.urljoin() function to PHP. Why this isn't in the standard library is anyone's guess.

Author: fluffy, http://beesbuzz.biz/
Latest version at: https://github.com/plaidfluff/php-urljoin

 */

function urljoin($base, $rel) {
	if (!$base) {
		return $rel;
	}

	if (!$rel) {
		return $base;
	}

	$uses_relative = array('', 'ftp', 'http', 'gopher', 'nntp', 'imap',
		'wais', 'file', 'https', 'shttp', 'mms',
		'prospero', 'rtsp', 'rtspu', 'sftp',
		'svn', 'svn+ssh', 'ws', 'wss');

	$pbase = parse_url($base);
	$prel = parse_url($rel);

	if ($prel === false || preg_match('/^[a-z0-9\-.]*[^a-z0-9\-.:][a-z0-9\-.]*:/i', $rel)) {
		/*
			Either parse_url couldn't parse this, or the original URL
			fragment had an invalid scheme character before the first :,
			which can confuse parse_url
		*/
		$prel = array('path' => $rel);
	}

	if (array_key_exists('path', $pbase) && $pbase['path'] === '/') {
		unset($pbase['path']);
	}

	if (isset($prel['scheme'])) {
		if (
			$prel['scheme'] != ($pbase['scheme'] ?? null)
			|| in_array($prel['scheme'], $uses_relative) == false
		) {
			return $rel;
		}
	}

	$merged = array_merge($pbase, $prel);

	// Handle relative paths:
	//   'path/to/file.ext'
	// './path/to/file.ext'
	if (array_key_exists('path', $prel) && substr($prel['path'], 0, 1) != '/') {

		// Normalize: './path/to/file.ext' => 'path/to/file.ext'
		if (substr($prel['path'], 0, 2) === './') {
			$prel['path'] = substr($prel['path'], 2);
		}

		if (array_key_exists('path', $pbase)) {
			$dir = preg_replace('@/[^/]*$@', '', $pbase['path']);
			$merged['path'] = $dir . '/' . $prel['path'];
		} else {
			$merged['path'] = '/' . $prel['path'];
		}

	}

	if(array_key_exists('path', $merged)) {
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
	}

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
