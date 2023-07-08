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

        $bridgeFactory = new BridgeFactory();

        $bridgeClassName = $bridgeFactory->createBridgeClassName($request['bridge'] ?? '');

        // whitelist control
        if (!$bridgeFactory->isEnabled($bridgeClassName)) {
            throw new \Exception('This bridge is not whitelisted', 401);
            die;
        }

        $bridge = $bridgeFactory->create($bridgeClassName);
        $bridge->loadConfiguration();
        $value = $request['value'];

        $cache = RssBridge::getCache();
        $cache->setScope(get_class($bridge));
        if (!is_array($key)) {
            // not sure if $key is an array when it comes in from request
            $key = [$key];
        }
        $cache->setKey($key);
        $cache->saveData($value);

        header('Content-Type: text/plain');
        echo 'done';
    }
}
