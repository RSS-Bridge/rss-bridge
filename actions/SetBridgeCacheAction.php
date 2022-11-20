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

        $key = $request['key'] or returnClientError('You must specify key!');

        $bridgeFactory = new \BridgeFactory();

        $bridgeClassName = null;
        if (isset($request['bridge'])) {
            $bridgeClassName = $bridgeFactory->sanitizeBridgeName($request['bridge']);
        }

        if ($bridgeClassName === null) {
            throw new \InvalidArgumentException('Bridge name invalid!');
        }

        // whitelist control
        if (!$bridgeFactory->isWhitelisted($bridgeClassName)) {
            throw new \Exception('This bridge is not whitelisted', 401);
            die;
        }

        $bridge = $bridgeFactory->create($bridgeClassName);
        $bridge->loadConfiguration();
        $value = $request['value'];

        $cacheFactory = new CacheFactory();

        $cache = $cacheFactory->create();
        $cache->setScope(get_class($bridge));
        $cache->setKey($key);
        $cache->saveData($value);

        header('Content-Type: text/plain');
        echo 'done';
    }
}
