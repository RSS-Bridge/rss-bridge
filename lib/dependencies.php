<?php

declare(strict_types=1);

$container = new Container();

$container[ConnectivityAction::class] = function ($c) {
    return new ConnectivityAction($c['bridge_factory']);
};

$container[DetectAction::class] = function ($c) {
    return new DetectAction($c['bridge_factory']);
};

$container[DisplayAction::class] = function ($c) {
    return new DisplayAction($c['cache'], $c['logger'], $c['bridge_factory']);
};

$container[FindfeedAction::class] = function ($c) {
    return new FindfeedAction($c['bridge_factory']);
};

$container[FrontpageAction::class] = function ($c) {
    return new FrontpageAction($c['bridge_factory']);
};

$container[HealthAction::class] = function () {
    return new HealthAction();
};

$container[ListAction::class] = function ($c) {
    return new ListAction($c['bridge_factory']);
};

$container['bridge_factory'] = function ($c) {
    return new BridgeFactory($c['cache'], $c['logger']);
};


$container['http_client'] = function () {
    return new CurlHttpClient();
};

$container['cache_factory'] = function ($c) {
    return new CacheFactory($c['logger']);
};

$container['logger'] = function () {
    $logger = new SimpleLogger('rssbridge');
    if (Configuration::getConfig('system', 'env') === 'dev') {
        $logger->addHandler(new ErrorLogHandler(Logger::DEBUG));
    } else {
        $logger->addHandler(new ErrorLogHandler(Logger::INFO));
    }

    $log_file_path  = Configuration::getConfig('logging', 'log_file_path');
    $log_file_level = Configuration::getConfig('logging', 'log_file_level');
    if ($log_file_path && $log_file_level) {
        $level = array_flip(Logger::LEVEL_NAMES)[strtoupper($log_file_level)];
        $logger->addHandler(new StreamHandler($log_file_path, $level));
    }

    return $logger;
};

$container['cache'] = function ($c) {
    /** @var CacheFactory $cacheFactory */
    $cacheFactory = $c['cache_factory'];
    $cache = $cacheFactory->create(Configuration::getConfig('cache', 'type'));
    return $cache;
};

return $container;
