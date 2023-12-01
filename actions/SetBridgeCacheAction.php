<?php

class SetBridgeCacheAction implements ActionInterface
{
    private CacheInterface $cache;

    public function __construct()
    {
        $this->cache = RssBridge::getCache();
    }

    public function execute(array $request)
    {
        $authenticationMiddleware = new ApiAuthenticationMiddleware();
        $authenticationMiddleware($request);

        $key = $request['key'] ?? null;
        if (!$key) {
            returnClientError('You must specify key!');
        }

        $bridgeFactory = new BridgeFactory();

        $bridgeName = $request['bridge'] ?? null;
        $bridgeClassName = $bridgeFactory->createBridgeClassName($bridgeName);
        if (!$bridgeClassName) {
            throw new \Exception(sprintf('Bridge not found: %s', $bridgeName));
        }

        // whitelist control
        if (!$bridgeFactory->isEnabled($bridgeClassName)) {
            throw new \Exception('This bridge is not whitelisted', 401);
        }

        $bridge = $bridgeFactory->create($bridgeClassName);
        $bridge->loadConfiguration();
        $value = $request['value'];

        $cacheKey = get_class($bridge) . '_' . $key;
        $ttl = 86400 * 3;
        $this->cache->set($cacheKey, $value, $ttl);

        header('Content-Type: text/plain');
        echo 'done';
    }
}
