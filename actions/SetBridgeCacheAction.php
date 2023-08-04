<?php

/**
 * This file is part of RSS-Bridge, a PHP project capable of generating RSS and
 * Atom feeds for websites that don't have one.
 *
 * For the full license information, please view the UNLICENSE file distributed
 * with this source code.
 *
 * @package Core
 * @license http://unlicense.org/ UNLICENSE
 * @link    https://github.com/rss-bridge/rss-bridge
 */

class SetBridgeCacheAction implements ActionInterface
{
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
            die;
        }

        $bridge = $bridgeFactory->create($bridgeClassName);
        $bridge->loadConfiguration();
        $value = $request['value'];

        $cache = RssBridge::getCache();

        $cacheKey = get_class($bridge) . '_' . $key;
        $ttl = 86400 * 3;
        $cache->set($cacheKey, $value, $ttl);

        header('Content-Type: text/plain');
        echo 'done';
    }
}
