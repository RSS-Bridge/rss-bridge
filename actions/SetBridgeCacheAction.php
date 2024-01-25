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
        // Authentication
        $accessTokenInConfig = Configuration::getConfig('authentication', 'access_token');
        if (!$accessTokenInConfig) {
            return new Response('Access token is not set in this instance', 403, ['content-type' => 'text/plain']);
        }
        if (isset($request['access_token'])) {
            $accessTokenGiven = $request['access_token'];
        } else {
            $header = trim($_SERVER['HTTP_AUTHORIZATION'] ?? '');
            $position = strrpos($header, 'Bearer ');
            if ($position !== false) {
                $accessTokenGiven = substr($header, $position + 7);
            } else {
                $accessTokenGiven = '';
            }
        }
        if (!$accessTokenGiven) {
            return new Response('No access token given', 403, ['content-type' => 'text/plain']);
        }
        if (! hash_equals($accessTokenInConfig, $accessTokenGiven)) {
            return new Response('Incorrect access token', 403, ['content-type' => 'text/plain']);
        }

        // Begin actual work
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
