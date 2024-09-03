<?php

declare(strict_types=1);

class CacheMiddleware implements Middleware
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public function __invoke(Request $request, $next): Response
    {
        $action = $request->attribute('action');

        if ($action !== 'DisplayAction') {
            // We only cache DisplayAction (for now)
            return $next($request);
        }

        // TODO: might want to remove som params from query
        $cacheKey = 'http_' . json_encode($request->toArray());
        $cachedResponse = $this->cache->get($cacheKey);

        if ($cachedResponse) {
            $ifModifiedSince = $request->server('HTTP_IF_MODIFIED_SINCE');
            $lastModified = $cachedResponse->getHeader('last-modified');
            if ($ifModifiedSince && $lastModified) {
                $lastModified = new \DateTimeImmutable($lastModified);
                $lastModifiedTimestamp = $lastModified->getTimestamp();
                $modifiedSince = strtotime($ifModifiedSince);
                // TODO: \DateTimeImmutable can be compared directly
                if ($lastModifiedTimestamp <= $modifiedSince) {
                    $modificationTimeGMT = gmdate('D, d M Y H:i:s ', $lastModifiedTimestamp);
                    return new Response('', 304, ['last-modified' => $modificationTimeGMT . 'GMT']);
                }
            }
            return $cachedResponse;
        }

        /** @var Response $response */
        $response = $next($request);

        if (in_array($response->getCode(), [403, 429, 500, 503])) {
            // Cache these responses for about ~10 mins on average
            $this->cache->set($cacheKey, $response, 60 * 5 + rand(1, 60 * 10));
        }

        // For 1% of requests, prune cache
        if (rand(1, 100) === 1) {
            // This might be resource intensive!
            $this->cache->prune();
        }

        return $response;
    }
}