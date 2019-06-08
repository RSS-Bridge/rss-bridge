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

/**
 * Checks if the website for a given bridge is reachable.
 *
 * **Remarks**
 * - This action is only available in debug mode.
 * - Returns the bridge status as Json-formatted string.
 * - Returns an error if the bridge is not whitelisted.
 * - Returns a responsive web page that automatically checks all whitelisted
 * bridges (using JavaScript) if no bridge is specified.
 */
class ConnectivityAction extends ActionAbstract {
	public function execute() {

		if(!Debug::isEnabled()) {
			returnError('This action is only available in debug mode!');
		}

		if(!isset($this->userData['bridge'])) {
			$this->returnEntryPage();
			return;
		}

		$bridgeName = $this->userData['bridge'];

		if(!Bridge::isWhitelisted($bridgeName)) {
			header('Content-Type: text/html');
			returnServerError('Bridge is not whitelisted!');
		}

		$this->reportBridgeConnectivity($bridgeName);

	}

	/**
	 * Generates a report about the bridge connectivity status and sends it back
	 * to the user.
	 *
	 * The report is generated as Json-formatted string in the format
	 * {
	 *   "bridge": "<bridge-name>",
	 *   "successful": true/false
	 * }
	 *
	 * @param string $bridgeName Name of the bridge to generate the report for
	 * @return void
	 */
	private function reportBridgeConnectivity($bridgeName) {

		header('Content-Type: text/json');

		$retVal = array(
			'bridge' => $bridgeName,
			'successful' => false
		);

		$bridge = Bridge::create($bridgeName);

		if($bridge === false) {
			echo json_encode($retVal);
			return;
		}

		$curl_opts = array(
			CURLOPT_CONNECTTIMEOUT => 5
		);

		try {
			$html = getContents($bridge::URI, array(), $curl_opts);

			if($html) {
				$retVal['successful'] = true;
			}
		} catch(Exception $e) {
			$retVal['successful'] = false;
		}

		echo json_encode($retVal);

	}

	private function returnEntryPage() {
	echo <<<EOD
<!DOCTYPE html>

<html>
	<head>
		<link
			rel="stylesheet"
			href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
			integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T"
			crossorigin="anonymous">
		<link
			rel="stylesheet"
			href="https://use.fontawesome.com/releases/v5.6.3/css/all.css"
			integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/"
			crossorigin="anonymous">
		<link rel="stylesheet" href="static/connectivity.css">
		<script src="static/connectivity.js" type="text/javascript"></script>
	</head>
	<body>
		<div id="main-content" class="container">
			<div id="status-message" class="alert alert-primary alert-dismissible fade show" role="alert">
				<span>...</span>
				<button type="button" class="close" data-dismiss="alert" aria-label="Close" onkeyup="stopConnectivityChecks()">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<input type="text" class="form-control" id="search" onkeyup="search()" placeholder="Search for bridge..">
		</div>
	</body>
</html>
EOD;
	}
}
