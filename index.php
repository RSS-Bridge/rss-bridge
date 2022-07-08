<?php

require_once __DIR__ . '/lib/rssbridge.php';

/*
Move the CLI arguments to the $_GET array, in order to be able to use
rss-bridge from the command line
*/
if (isset($argv)) {
    parse_str(implode('&', array_slice($argv, 1)), $cliArgs);
    $request = array_merge($_GET, $cliArgs);
} else {
    $request = $_GET;
}

try {
    foreach ($request as $value) {
        if (! is_string($value)) {
            http_response_code(400);
            print render('error.html.php', ['message' => '400 Bad Request']);
            exit;
        }
    }

    $actionFactory = new ActionFactory();

    if (array_key_exists('action', $request)) {
        $action = $actionFactory->create($request['action']);

        $action->execute($request);
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

    http_response_code(500);
    print render('error.html.php', [
        'message' => $message,
        'stacktrace' => create_sane_stacktrace($e),
    ]);
}
