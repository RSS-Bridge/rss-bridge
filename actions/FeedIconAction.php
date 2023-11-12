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

    public function execute(array $request): void
    {
        $bridgeClassName = $request['bridgeClassName'] ?? null;

        if (!$bridgeClassName || !class_exists($bridgeClassName)) {
            $this->sendNotFoundResponse();
        }

        $cacheKey = $this->buildCacheKey($bridgeClassName);

        list(
            'imageData' => $imageData,
            'mimeType' => $mimeType
        ) = $this->readCache($cacheKey);

        if ($imageData && $mimeType) {
            $this->sendImageResponse($mimeType, $imageData);
        } elseif ('' === $imageData && '' === $mimeType) {
            // empty values means that the image was broken and could not be loaded
            $this->sendNotFoundResponse();
        }

        $bridge = $this->bridgeFactory->create($bridgeClassName);
        $imageUrl = $bridge->getIcon();

        $imageUrl = $this->cleanImageUrl($imageUrl);

        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            //store empty values to prevent further attempts to load broken image
            $this->writeCache($cacheKey, '', '');
            $this->sendNotFoundResponse();
        }

        list(
            'imageData' => $imageData,
            'mimeType' => $mimeType
        ) = $this->readImageData($imageUrl);

        if (false === $imageData || false === $mimeType) {
            //store empty values to prevent further attempts to load broken image
            $this->writeCache($cacheKey, '', '');
            $this->sendNotFoundResponse();
        }

        $this->writeCache($cacheKey, $mimeType, $imageData);
        $this->sendImageResponse($mimeType, $imageData);
    }

    private function sendNotFoundResponse(): void
    {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    private function sendImageResponse(string $mimeType, string $imageData): void
    {
        header('Content-Type: ' . $mimeType);
        echo $imageData;
        exit;
    }

    private function buildCacheKey(string $bridgeClassName): string
    {
        return md5($bridgeClassName . 'icon');
    }

    private function cleanImageUrl(string $imageUrl): string
    {
        //negative look behind: replace duplicate slashes in path, but not in protocol part of uri
        $imageUrl = preg_replace('~(?<!:)//~', '/', $imageUrl);
        return str_replace(["\r", "\n"], '', $imageUrl);
    }

    private function writeCache(string $cacheKey, string $mimeType, string $imageData): void
    {
        $this->cache->set($cacheKey, ['imageData' => $imageData, 'mimeType' => $mimeType]);
    }

    private function readCache(string $cacheKey): array
    {
        return (array)$this->cache->get($cacheKey);
    }

    private function readImageData(string $imageUrl): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
            ],
        ]);

        $handle = @fopen($imageUrl, 'r', false, $context);

        if ($handle === false) {
            return ['imageData' => false, 'mimeType' => false];
        }
        $metaData = stream_get_meta_data($handle);
        $headers = $metaData['wrapper_data'];

        $contentType = false;

        foreach ($headers as $header) {
            if (stripos($header, 'Content-Type:') === 0) {
                $contentType = trim(substr($header, 13)); // 13 is the length of "Content-Type:"
                break;
            }
        }

        $imageData = stream_get_contents($handle);

        fclose($handle);

        return ['imageData' => $imageData, 'mimeType' => $contentType];
    }
}
