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
	$actionFac = new ActionFactory();

	if (array_key_exists('action', $params)) {
		$action = $actionFac->create($params['action']);
		$action->userData = $params;
		$action->execute();
	} else {
		$showInactive = filter_input(INPUT_GET, 'show_inactive', FILTER_VALIDATE_BOOLEAN);
		echo BridgeList::create($showInactive);
	}
} catch (\Throwable $e) {
	error_log($e);

	$code = $e->getCode();
	if ($code !== -1) {
		header('Content-Type: text/plain', true, $code);
	}

	$message = sprintf("Uncaught Exception %s: '%s'\n", get_class($e), $e->getMessage());

	print $message;
}
