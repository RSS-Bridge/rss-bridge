<?php

class SetBridgeCacheAction implements ActionInterface
{
    private CacheInterface $cache;

    public function __construct()
    {
        $this->cache = RssBridge::getCache();
    }

    public function execute(Request $request)
    {
        $requestArray = $request->toArray();

        // Authentication
        $accessTokenInConfig = Configuration::getConfig('authentication', 'access_token');
        if (!$accessTokenInConfig) {
            return new Response('Access token is not set in this instance', 403, ['content-type' => 'text/plain']);
        }
        if (isset($requestArray['access_token'])) {
            $accessTokenGiven = $requestArray['access_token'];
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
        $key = $requestArray['key'] ?? null;
        if (!$key) {
            return new Response('You must specify key', 400, ['content-type' => 'text/plain']);
        }

        $bridgeFactory = new BridgeFactory();

        $bridgeName = $requestArray['bridge'] ?? null;
        $bridgeClassName = $bridgeFactory->createBridgeClassName($bridgeName);
        if (!$bridgeClassName) {
            return new Response(sprintf('Bridge not found: %s', $bridgeName), 400, ['content-type' => 'text/plain']);
        }

        // whitelist control
        if (!$bridgeFactory->isEnabled($bridgeClassName)) {
            return new Response('This bridge is not whitelisted', 401, ['content-type' => 'text/plain']);
        }

        $bridge = $bridgeFactory->create($bridgeClassName);
        $bridge->loadConfiguration();
        $value = $requestArray['value'];

        $cacheKey = get_class($bridge) . '_' . $key;
        $ttl = 86400 * 3;
        $this->cache->set($cacheKey, $value, $ttl);

        return new Response('done', 200, ['Content-Type' => 'text/plain']);
    }
}
