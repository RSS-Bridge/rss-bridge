<?php

class FeedIconAction implements ActionInterface
{
    private CacheInterface $cache;
    private BridgeFactory $bridgeFactory;

    public function __construct()
    {
        $this->cache = RssBridge::getCache();
        $this->bridgeFactory = new BridgeFactory();
    }

    public function execute(array $request)
    {
        $bridgeClassName = $request['bridgeClassName'] ?? null;

        if (!$bridgeClassName) {
            $this->sendNotFoundResponse();
        }

        $cacheKey = $this->buildCacheKey($bridgeClassName);

        if ($cachedImageData = $this->cache->get($cacheKey)) {
            $mimeType = $this->cache->get($cacheKey . 'mimeType');
            if ($mimeType) {
                $this->sendImageResponse($mimeType, $cachedImageData);
            }
        }

        $bridge = $this->bridgeFactory->create($bridgeClassName);
        $image_url = $bridge->getIcon();

        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            $this->sendNotFoundResponse();
        }

        $image_data = file_get_contents($image_url);

        if ($image_data === false) {
            $this->sendNotFoundResponse();
        }

        $image_info = getimagesize($image_url);
        if ($image_info === false) {
            $this->sendNotFoundResponse();
        }

        $mimeType = $image_info['mime'];
        $this->cache->set($cacheKey . 'mimeType', $mimeType);
        $this->cache->set($cacheKey, $image_data);

        $this->sendImageResponse($mimeType, $image_data);
    }

    private function sendNotFoundResponse()
    {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    private function sendImageResponse($mimeType, $imageData)
    {
        header('Content-Type: ' . $mimeType);
        echo $imageData;
        exit;
    }

    public function buildCacheKey($bridgeClassName): string
    {
        return md5($bridgeClassName . 'icon');
    }
}
