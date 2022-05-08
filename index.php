<?php

require_once __DIR__ . '/lib/rssbridge.php';

/*
Move the CLI arguments to the $_GET array, in order to be able to use
rss-bridge from the command line
*/
if (isset($argv)) {
	parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
	$params = array_merge($_GET, $cliArgs);
} else {
	$params = $_GET;
}

try {

	$actionFac = new \ActionFactory();
	$actionFac->setWorkingDir(PATH_LIB_ACTIONS);

	if(array_key_exists('action', $params)) {
		$action = $actionFac->create($params['action']);
		$action->setUserData($params);
		$action->execute();
	} else {
		$showInactive = filter_input(INPUT_GET, 'show_inactive', FILTER_VALIDATE_BOOLEAN);
		echo BridgeList::create($showInactive);
	}
} catch(\Exception $e) {
	error_log($e);
	$code = $e->getCode();
	if ($code !== -1) {
		header('Content-Type: text/plain', true, $code);
	}
	die($e->getMessage());
}
