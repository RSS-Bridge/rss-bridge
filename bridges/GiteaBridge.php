<?php
/**
 * Gitea is a fork of Gogs which may diverge in the future.
 * https://docs.gitea.io/en-us/
 */
require_once 'GogsBridge.php';

class GiteaBridge extends GogsBridge {

	const NAME = 'Gitea';
	const URI = 'https://gitea.io';
	const DESCRIPTION = 'Returns the latest issues, commits or releases';
	const MAINTAINER = 'logmanoriginal';
	const CACHE_TIMEOUT = 300; // 5 minutes

	protected function collectReleasesData($html) {
		$releases = $html->find('#release-list > li')
			or returnServerError('Unable to find releases');

		foreach($releases as $release) {
			$this->items[] = array(
				'uri' => $release->find('a', 0)->href,
				'title' => 'Release ' . $release->find('h3', 0)->plaintext,
			);
		}
	}
}
