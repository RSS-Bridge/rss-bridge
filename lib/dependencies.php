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
    if (Debug::isEnabled()) {
        $logger->addHandler(new ErrorLogHandler(Logger::DEBUG));
    } else {
        $logger->addHandler(new ErrorLogHandler(Logger::INFO));
    }
    // Uncomment this for info logging to fs
    // $logger->addHandler(new StreamHandler('/tmp/rss-bridge.txt', Logger::INFO));

    // Uncomment this for debug logging to fs
    // $logger->addHandler(new StreamHandler('/tmp/rss-bridge-debug.txt', Logger::DEBUG));
    return $logger;
};

$container['cache'] = function ($c) {
    /** @var CacheFactory $cacheFactory */
    $cacheFactory = $c['cache_factory'];
    $cache = $cacheFactory->create(Configuration::getConfig('cache', 'type'));
    return $cache;
};

return $container;
