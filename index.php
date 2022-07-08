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
    $actionFactory = new ActionFactory();

    if (array_key_exists('action', $params)) {
        $action = $actionFactory->create($params['action']);
        $action->userData = $params;
        $action->execute();
    } else {
        $showInactive = filter_input(INPUT_GET, 'show_inactive', FILTER_VALIDATE_BOOLEAN);
        echo BridgeList::create($showInactive);
    }
} catch (\Throwable $e) {
    error_log($e);

    $message = sprintf(
        'Uncaught Exception %s: %s at %s line %s',
        get_class($e),
        $e->getMessage(),
        trim_path_prefix($e->getFile()),
        $e->getLine()
    );

    print render('error.html.php', [
        'message' => $message,
        'stacktrace' => create_sane_stacktrace($e),
    ]);
}
