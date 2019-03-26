<?php
/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package	Core
 * @license	http://unlicense.org/ UNLICENSE
 * @link	https://github.com/rss-bridge/rss-bridge
 */

class DetectAction extends ActionAbstract {
	public function execute() {
		$targetURL = $this->userData['url']
			or returnClientError('You must specify a url!');

		$format = $this->userData['format']
			or returnClientError('You must specify a format!');

		foreach(Bridge::getBridgeNames() as $bridgeName) {

			if(!Bridge::isWhitelisted($bridgeName)) {
				continue;
			}

			$bridge = Bridge::create($bridgeName);

			if($bridge === false) {
				continue;
			}

			$bridgeParams = $bridge->detectParameters($targetURL);

			if(is_null($bridgeParams)) {
				continue;
			}

			$bridgeParams['bridge'] = $bridgeName;
			$bridgeParams['format'] = $format;

			header('Location: ?action=display&' . http_build_query($bridgeParams), true, 301);
			die();

		}

		returnClientError('No bridge found for given URL: ' . $targetURL);
	}
}
