<?php

class DisplayAction implements ActionInterface
{
    private CacheInterface $cache;
    private Logger $logger;

    public function __construct()
    {
        $this->cache = RssBridge::getCache();
        $this->logger = RssBridge::getLogger();
    }

    public function execute(Request $request)
    {
        $bridgeName = $request->get('bridge');
        $format = $request->get('format');
        $noproxy = $request->get('_noproxy');

        $cacheKey = 'http_' . json_encode($request->toArray());
        /** @var Response $cachedResponse */
        $cachedResponse = $this->cache->get($cacheKey);
        if ($cachedResponse) {
            $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
            $lastModified = $cachedResponse->getHeader('last-modified');
            if ($ifModifiedSince && $lastModified) {
                $lastModified = new \DateTimeImmutable($lastModified);
                $lastModifiedTimestamp = $lastModified->getTimestamp();
                $modifiedSince = strtotime($ifModifiedSince);
                if ($lastModifiedTimestamp <= $modifiedSince) {
                    $modificationTimeGMT = gmdate('D, d M Y H:i:s ', $lastModifiedTimestamp);
                    return new Response('', 304, ['last-modified' => $modificationTimeGMT . 'GMT']);
                }
            }
            return $cachedResponse->withHeader('rss-bridge', 'This is a cached response');
        }

        if (!$bridgeName) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'Missing bridge parameter']), 400);
        }
        $bridgeFactory = new BridgeFactory();
        $bridgeClassName = $bridgeFactory->createBridgeClassName($bridgeName);
        if (!$bridgeClassName) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'Bridge not found']), 404);
        }

        if (!$format) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'You must specify a format']), 400);
        }
        if (!$bridgeFactory->isEnabled($bridgeClassName)) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'This bridge is not whitelisted']), 400);
        }

        if (
            Configuration::getConfig('proxy', 'url')
            && Configuration::getConfig('proxy', 'by_bridge')
            && $noproxy
        ) {
            // This const is only used once in getContents()
            define('NOPROXY', true);
        }

        $bridge = $bridgeFactory->create($bridgeClassName);

        $response = $this->createResponse($request, $bridge, $format);

        if ($response->getCode() === 200) {
            $ttl = $request->get('_cache_timeout');
            if (Configuration::getConfig('cache', 'custom_timeout') && $ttl) {
                $ttl = (int) $ttl;
            } else {
                $ttl = $bridge->getCacheTimeout();
            }
            $this->cache->set($cacheKey, $response, $ttl);
        }

        if (in_array($response->getCode(), [403, 429, 503])) {
            // Cache these responses for about ~20 mins on average
            $this->cache->set($cacheKey, $response, 60 * 15 + rand(1, 60 * 10));
        }

        if ($response->getCode() === 500) {
            $this->cache->set($cacheKey, $response, 60 * 15);
        }

        if (rand(1, 100) === 2) {
            $this->cache->prune();
        }

        return $response;
    }

    private function createResponse(Request $request, BridgeAbstract $bridge, string $format)
    {
        $items = [];
        $feed = [];

        try {
            $bridge->loadConfiguration();
            // Remove parameters that don't concern bridges
            $remove = [
                'token',
                'action',
                'bridge',
                'format',
                '_noproxy',
                '_cache_timeout',
                '_error_time',
                '_', // Some RSS readers add a cache-busting parameter (_=<timestamp>) to feed URLs, detect and ignore them.
            ];
            $requestArray = $request->toArray();
            $input = array_diff_key($requestArray, array_fill_keys($remove, ''));
            $bridge->setInput($input);
            $bridge->collectData();
            $items = $bridge->getItems();
            if (isset($items[0]) && is_array($items[0])) {
                $feedItems = [];
                foreach ($items as $item) {
                    $feedItems[] = FeedItem::fromArray($item);
                }
                $items = $feedItems;
            }
            $feed = $bridge->getFeed();
        } catch (\Exception $e) {
            // Probably an exception inside a bridge
            if ($e instanceof HttpException) {
                // Reproduce (and log) these responses regardless of error output and report limit
                if ($e->getCode() === 429) {
                    $this->logger->info(sprintf('Exception in DisplayAction(%s): %s', $bridge->getShortName(), create_sane_exception_message($e)));
                    return new Response(render(__DIR__ . '/../templates/exception.html.php', ['e' => $e]), 429);
                }
                if ($e->getCode() === 503) {
                    $this->logger->info(sprintf('Exception in DisplayAction(%s): %s', $bridge->getShortName(), create_sane_exception_message($e)));
                    return new Response(render(__DIR__ . '/../templates/exception.html.php', ['e' => $e]), 503);
                }
            }
            $this->logger->error(sprintf('Exception in DisplayAction(%s)', $bridge->getShortName()), ['e' => $e]);
            $errorOutput = Configuration::getConfig('error', 'output');
            $reportLimit = Configuration::getConfig('error', 'report_limit');
            $errorCount = 1;
            if ($reportLimit > 1) {
                $errorCount = $this->logBridgeError($bridge->getName(), $e->getCode());
            }
            // Let clients know about the error if we are passed the report limit
            if ($errorCount >= $reportLimit) {
                if ($errorOutput === 'feed') {
                    // Render the exception as a feed item
                    $items[] = $this->createFeedItemFromException($e, $bridge);
                } elseif ($errorOutput === 'http') {
                    return new Response(render(__DIR__ . '/../templates/exception.html.php', ['e' => $e]), 500);
                } elseif ($errorOutput === 'none') {
                    // Do nothing (produces an empty feed)
                }
            }
        }

        $formatFactory = new FormatFactory();
        $format = $formatFactory->create($format);

        $format->setItems($items);
        $format->setFeed($feed);
        $now = time();
        $format->setLastModified($now);
        $headers = [
            'last-modified' => gmdate('D, d M Y H:i:s ', $now) . 'GMT',
            'content-type'  => $format->getMimeType() . '; charset=' . $format->getCharset(),
        ];
        return new Response($format->stringify(), 200, $headers);
    }

    private function createFeedItemFromException($e, BridgeAbstract $bridge): FeedItem
    {
        $item = new FeedItem();

        // Create a unique identifier every 24 hours
        $uniqueIdentifier = urlencode((int)(time() / 86400));
        $title = sprintf('Bridge returned error %s! (%s)', $e->getCode(), $uniqueIdentifier);
        $item->setTitle($title);
        $item->setURI(get_current_url());
        $item->setTimestamp(time());

        // Create an item identifier for feed readers e.g. "staysafetv twitch videos_19389"
        $item->setUid($bridge->getName() . '_' . $uniqueIdentifier);

        $content = render_template(__DIR__ . '/../templates/bridge-error.html.php', [
            'error' => render_template(__DIR__ . '/../templates/exception.html.php', ['e' => $e]),
            'searchUrl' => self::createGithubSearchUrl($bridge),
            'issueUrl' => self::createGithubIssueUrl($bridge, $e, create_sane_exception_message($e)),
            'maintainer' => $bridge->getMaintainer(),
        ]);
        $item->setContent($content);
        return $item;
    }

    private function logBridgeError($bridgeName, $code)
    {
        // todo: it's not really necessary to json encode $report
        $cacheKey = 'error_reporting_' . $bridgeName . '_' . $code;
        $report = $this->cache->get($cacheKey);
        if ($report) {
            $report = Json::decode($report);
            $report['time'] = time();
            $report['count']++;
        } else {
            $report = [
                'error' => $code,
                'time' => time(),
                'count' => 1,
            ];
        }
        $ttl = 86400 * 5;
        $this->cache->set($cacheKey, Json::encode($report), $ttl);
        return $report['count'];
    }

    private static function createGithubIssueUrl($bridge, $e, string $message): string
    {
        return sprintf('https://github.com/RSS-Bridge/rss-bridge/issues/new?%s', http_build_query([
            'title' => sprintf('%s failed with error %s', $bridge->getName(), $e->getCode()),
            'body' => sprintf(
                "```\n%s\n\n%s\n\nQuery string: %s\nVersion: %s\nOs: %s\nPHP version: %s\n```",
                $message,
                implode("\n", trace_to_call_points(trace_from_exception($e))),
                $_SERVER['QUERY_STRING'] ?? '',
                Configuration::getVersion(),
                PHP_OS_FAMILY,
                phpversion() ?: 'Unknown'
            ),
            'labels' => 'Bridge-Broken',
            'assignee' => $bridge->getMaintainer(),
        ]));
    }

    private static function createGithubSearchUrl($bridge): string
    {
        return sprintf(
            'https://github.com/RSS-Bridge/rss-bridge/issues?q=%s',
            urlencode('is:issue is:open ' . $bridge->getName())
        );
    }
}
