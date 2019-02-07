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

class ListAction extends ActionAbstract {
	public function execute() {
		$list = new StdClass();
		$list->bridges = array();
		$list->total = 0;

		foreach(Bridge::getBridgeNames() as $bridgeName) {

			$bridge = Bridge::create($bridgeName);

			if($bridge === false) { // Broken bridge, show as inactive

				$list->bridges[$bridgeName] = array(
					'status' => 'inactive'
				);

				continue;

			}

			$status = Bridge::isWhitelisted($bridgeName) ? 'active' : 'inactive';

			$list->bridges[$bridgeName] = array(
				'status' => $status,
				'uri' => $bridge->getURI(),
				'name' => $bridge->getName(),
				'icon' => $bridge->getIcon(),
				'parameters' => $bridge->getParameters(),
				'maintainer' => $bridge->getMaintainer(),
				'description' => $bridge->getDescription()
			);

		}

		$list->total = count($list->bridges);

		header('Content-Type: application/json');
		echo json_encode($list, JSON_PRETTY_PRINT);
	}
}
