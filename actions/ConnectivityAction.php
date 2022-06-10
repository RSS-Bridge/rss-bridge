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
 *
 * The report is generated as Json-formatted string in the format
 * {
 *   "bridge": "<bridge-name>",
 *   "successful": true/false,
 *   "http_code": code
 * }
 *
 * If check_items is passed, the additional keys will be present:
 * {
 *   "valid_items": true/false,
 *   "failed_on": null/array,
 * }
 */
class ConnectivityAction extends ActionAbstract {
	public function execute() {

		if(!Debug::isEnabled()) {
			returnError('This action is only available in debug mode!', 400);
		}

		// Return the javascript tester when visiting the main page
		if(!isset($this->userData['bridge'])) {
			$this->returnEntryPage();
			return;
		}

		$bridgeName = $this->userData['bridge'];
		$bridgeFac = new \BridgeFactory();

		if(!$bridgeFac->isWhitelisted($bridgeName)) {
			header('Content-Type: text/html');
			returnServerError('Bridge is not whitelisted!');
		}

		$bridge = $bridgeFac->create($bridgeName);

		$retVal = array_merge(
			['bridge' => $bridgeName],
			$this->testBridgeConnectivity($bridge)
		);
		if (isset($this->userData['check_items'])) {
			$retVal = array_merge($retVal, $this->testBridgeItems($bridge));
		}
		$this->generateReport($retVal);

	}

	private function generateReport($values) {
		header('Content-Type: text/json');
		echo json_encode($values);
	}

	/**
	 * Gets information on bridge connectivity status
	 *
	 * @param string $bridge instance of the bridge to generate the report for
	 * @return array
	 */
	private function testBridgeConnectivity($bridge) {

		$retVal = array(
			'successful' => false,
			'http_code' => 200,
		);

		$curl_opts = array(
			CURLOPT_CONNECTTIMEOUT => 5
		);

		try {
			$reply = getContents($bridge::URI, array(), $curl_opts, true);

			if($reply['code'] === 200) {
				$retVal['successful'] = true;
				if (strpos(implode('', $reply['status_lines']), '301 Moved Permanently')) {
					$retVal['http_code'] = 301;
				}
			}
		} catch(Exception $e) {
			$retVal['successful'] = false;
		}

		return $retVal;
	}

	private function testBridgeItems($bridge) {
		$retVal = ['valid_items' => true];
		$params = $bridge->getTestParameters();
		foreach($params as $set) {
			$bridge->setDatas($set);
			$bridge->collectData();
			if (!$bridge->checkItems()) {
				$retVal['valid_items'] = false;
				$retVal['failed_on'] = $set;
				break;
			}
			$bridge->clearDatas();
		}
		return $retVal;
	}

	private function returnEntryPage() {
		echo <<<EOD
<!DOCTYPE html>

<html>
	<head>
		<link rel="stylesheet" href="static/bootstrap.min.css">
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
			<div class="progress">
				<div class="progress-bar" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
			</div>
			<div id="status-message" class="sticky-top alert alert-primary alert-dismissible fade show" role="alert">
				<i id="status-icon" class="fas fa-sync"></i>
				<span>...</span>
				<button type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="stopConnectivityChecks()">
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
