<?php
final class BridgeList {

	private static function getHead() {
		return <<<EOD
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="RSS-Bridge" />
	<title>RSS-Bridge</title>
	<link href="static/style.css" rel="stylesheet">
	<script src="static/search.js"></script>
	<script src="static/select.js"></script>
	<noscript>
		<style>
			.searchbar {
				display: none;
			}
		</style>
	</noscript>
</head>
EOD;
	}

	private static function getBridges($whitelist, $showInactive, &$totalBridges, &$totalActiveBridges) {

		$body = '';
		$totalActiveBridges = 0;
		$inactiveBridges = '';

		$bridgeList = Bridge::listBridges();
		$formats = Format::searchInformation();

		$totalBridges = count($bridgeList);

		foreach($bridgeList as $bridgeName) {

			if(Bridge::isWhitelisted($whitelist, strtolower($bridgeName))) {

				$body .= BridgeCard::displayBridgeCard($bridgeName, $formats);
				$totalActiveBridges++;

			} elseif($showInactive) {

				// inactive bridges
				$inactiveBridges .= BridgeCard::displayBridgeCard($bridgeName, $formats, false) . PHP_EOL;

			}

		}

		$body .= $inactiveBridges;

		return $body;
	}

	private static function getHeader() {
		$warning = '';

		if(defined('DEBUG') && DEBUG === true) {
			if(defined('DEBUG_INSECURE') && DEBUG_INSECURE === true) {
				$warning .= <<<EOD
<section class="critical-warning">Warning : Debug mode is active from any location, 
make sure only you can access RSS-Bridge.</section>
EOD;
			} else {
				$warning .= <<<EOD
<section class="warning">Warning : Debug mode is active from your IP address, 
your requests will bypass the cache.</section>
EOD;
			}
		}

		return <<<EOD
<header>
	<h1>RSS-Bridge</h1>
	<h2>Reconnecting the Web</h2>
	{$warning}
</header>
EOD;
	}

	private static function getSearchbar() {
		$query = filter_input(INPUT_GET, 'q');

		return <<<EOD
<section class="searchbar">
	<h3>Search</h3>
	<input type="text" name="searchfield"
		id="searchfield" placeholder="Enter the bridge you want to search for"
		onchange="search()" onkeyup="search()" value="{$query}">
</section>
EOD;
	}

	private static function getFooter($totalBridges, $totalActiveBridges, $showInactive) {
		$version = Configuration::getVersion();

		$inactive = '';

		if($totalActiveBridges !== $totalBridges) {

			if(!$showInactive) {
				$inactive = '<a href="?show_inactive=1"><button class="small">Show inactive bridges</button></a><br>';
			} else {
				$inactive = '<a href="?show_inactive=0"><button class="small">Hide inactive bridges</button></a><br>';
			}

		}

		return <<<EOD
<section class="footer">
	<a href="https://github.com/rss-bridge/rss-bridge">RSS-Bridge ~ Public Domain</a><br>
	<p class="version">{$version}</p>
	{$totalActiveBridges}/{$totalBridges} active bridges.<br>
	{$inactive}
</section>
EOD;
	}

	static function create($whitelist, $showInactive = true) {

		$totalBridges = 0;
		$totalActiveBridges = 0;

		return '<!DOCTYPE html><html lang="en">'
		. BridgeList::getHead()
		. '<body onload="search()">'
		. BridgeList::getHeader()
		. BridgeList::getSearchbar()
		. BridgeList::getBridges($whitelist, $showInactive, $totalBridges, $totalActiveBridges)
		. BridgeList::getFooter($totalBridges, $totalActiveBridges, $showInactive)
		. '</body></html>';

	}
}
