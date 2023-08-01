<?php

class DisplayAction implements ActionInterface
{
    private CacheInterface $cache;

    public function execute(array $request)
    {
        if (Configuration::getConfig('system', 'enable_maintenance_mode')) {
            return new Response('503 Service Unavailable', 503);
        }
        $this->cache = RssBridge::getCache();
        $this->cache->setScope('http');
        $this->cache->setKey($request);
        // avg timeout of 20m
        $timeout = 60 * 15 + rand(1, 60 * 10);
        /** @var Response $cachedResponse */
        $cachedResponse = $this->cache->loadData($timeout);
        if ($cachedResponse && !Debug::isEnabled()) {
            //Logger::info(sprintf('Returning cached (http) response: %s', $cachedResponse->getBody()));
            return $cachedResponse;
        }
        $response = $this->createResponse($request);
        if (in_array($response->getCode(), [429, 503])) {
            //Logger::info(sprintf('Storing cached (http) response: %s', $response->getBody()));
            $this->cache->setScope('http');
            $this->cache->setKey($request);
            $this->cache->saveData($response);
        }
        return $response;
    }

    private function createResponse(array $request)
    {
        $bridgeFactory = new BridgeFactory();
        $formatFactory = new FormatFactory();

        $bridgeName = $request['bridge'] ?? null;
        $format = $request['format'] ?? null;

        $bridgeClassName = $bridgeFactory->createBridgeClassName($bridgeName);
        if (!$bridgeClassName) {
            throw new \Exception(sprintf('Bridge not found: %s', $bridgeName));
        }
        if (!$format) {
            throw new \Exception('You must specify a format!');
        }
        if (!$bridgeFactory->isEnabled($bridgeClassName)) {
            throw new \Exception('This bridge is not whitelisted');
        }

        $format = $formatFactory->create($format);

        $bridge = $bridgeFactory->create($bridgeClassName);
        $bridge->loadConfiguration();

        $noproxy = $request['_noproxy'] ?? null;
        if (
            Configuration::getConfig('proxy', 'url')
            && Configuration::getConfig('proxy', 'by_bridge')
            && $noproxy
        ) {
            // This const is only used once in getContents()
            define('NOPROXY', true);
        }

        $cacheTimeout = $request['_cache_timeout'] ?? null;
        if (Configuration::getConfig('cache', 'custom_timeout') && $cacheTimeout) {
            $cacheTimeout = (int) $cacheTimeout;
        } else {
            // At this point the query argument might still be in the url but it won't be used
            $cacheTimeout = $bridge->getCacheTimeout();
        }

        // Remove parameters that don't concern bridges
        $bridge_params = array_diff_key(
            $request,
            array_fill_keys(
                [
                    'action',
                    'bridge',
                    'format',
                    '_noproxy',
                    '_cache_timeout',
                    '_error_time'
                ],
                ''
            )
        );

        // Remove parameters that don't concern caches
        $cache_params = array_diff_key(
            $request,
            array_fill_keys(
                [
                    'action',
                    'format',
                    '_noproxy',
                    '_cache_timeout',
                    '_error_time'
                ],
                ''
            )
        );

        $this->cache->setScope('');
        $this->cache->setKey($cache_params);

        $items = [];
        $infos = [];

        $feed = $this->cache->loadData($cacheTimeout);

        if ($feed && !Debug::isEnabled()) {
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $modificationTime = $this->cache->getTime();
                // The client wants to know if the feed has changed since its last check
                $modifiedSince = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                if ($modificationTime <= $modifiedSince) {
                    $modificationTimeGMT = gmdate('D, d M Y H:i:s ', $modificationTime);
                    return new Response('', 304, ['Last-Modified' => $modificationTimeGMT . 'GMT']);
                }
            }

            if (isset($feed['items']) && isset($feed['extraInfos'])) {
                foreach ($feed['items'] as $item) {
                    $items[] = new FeedItem($item);
                }
                $infos = $feed['extraInfos'];
            }
        } else {
            try {
                $bridge->setDatas($bridge_params);
                $bridge->collectData();
                $items = $bridge->getItems();
                if (isset($items[0]) && is_array($items[0])) {
                    $feedItems = [];
                    foreach ($items as $item) {
                        $feedItems[] = new FeedItem($item);
                    }
                    $items = $feedItems;
                }
                $infos = [
                    'name' => $bridge->getName(),
                    'uri'  => $bridge->getURI(),
                    'donationUri'  => $bridge->getDonationURI(),
                    'icon' => $bridge->getIcon()
                ];
            } catch (\Exception $e) {
                $errorOutput = Configuration::getConfig('error', 'output');
                $reportLimit = Configuration::getConfig('error', 'report_limit');
                if ($e instanceof HttpException) {
                    // Reproduce (and log) these responses regardless of error output and report limit
                    if ($e->getCode() === 429) {
                        Logger::info(sprintf('Exception in DisplayAction(%s): %s', $bridgeClassName, create_sane_exception_message($e)));
                        return new Response('429 Too Many Requests', 429);
                    }
                    if ($e->getCode() === 503) {
                        Logger::info(sprintf('Exception in DisplayAction(%s): %s', $bridgeClassName, create_sane_exception_message($e)));
                        return new Response('503 Service Unavailable', 503);
                    }
                    // Might want to cache other codes such as 504 Gateway Timeout
                }
                if (in_array($errorOutput, ['feed', 'none'])) {
                    Logger::error(sprintf('Exception in DisplayAction(%s): %s', $bridgeClassName, create_sane_exception_message($e)), ['e' => $e]);
                }
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
                        // Rethrow so that the main exception handler in RssBridge.php produces an HTTP 500
                        throw $e;
                    } elseif ($errorOutput === 'none') {
                        // Do nothing (produces an empty feed)
                    } else {
                        // Do nothing, unknown error output? Maybe throw exception or validate in Configuration.php
                    }
                }
            }

            // Unfortunately need to set scope and key again because they might be modified
            $this->cache->setScope('');
            $this->cache->setKey($cache_params);
            $this->cache->saveData([
                'items' => array_map(function (FeedItem $item) {
                    return $item->toArray();
                }, $items),
                'extraInfos' => $infos
            ]);
            $this->cache->purgeCache();
        }

        $format->setItems($items);
        $format->setExtraInfos($infos);
        $newModificationTime = $this->cache->getTime();
        $format->setLastModified($newModificationTime);
        $headers = [];
        if ($newModificationTime) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s ', $newModificationTime) . 'GMT';
        }
        $headers['Content-Type'] = $format->getMimeType() . '; charset=' . $format->getCharset();
        return new Response($format->stringify(), 200, $headers);
    }

    private function createFeedItemFromException($e, BridgeInterface $bridge): FeedItem
    {
        $item = new FeedItem();

        // Create a unique identifier every 24 hours
        $uniqueIdentifier = urlencode((int)(time() / 86400));
        $itemTitle = sprintf('Bridge returned error %s! (%s)', $e->getCode(), $uniqueIdentifier);
        $item->setTitle($itemTitle);
        $item->setURI(get_current_url());
        $item->setTimestamp(time());

        // Create an item identifier for feed readers e.g. "staysafetv twitch videos_19389"
        $item->setUid($bridge->getName() . '_' . $uniqueIdentifier);

        $content = render_template(__DIR__ . '/../templates/bridge-error.html.php', [
            'error' => render_template(__DIR__ . '/../templates/error.html.php', ['e' => $e]),
            'searchUrl' => self::createGithubSearchUrl($bridge),
            'issueUrl' => self::createGithubIssueUrl($bridge, $e, create_sane_exception_message($e)),
            'maintainer' => $bridge->getMaintainer(),
        ]);
        $item->setContent($content);
        return $item;
    }

    private function logBridgeError($bridgeName, $code)
    {
        $this->cache->setScope('error_reporting');
        $this->cache->setkey([$bridgeName . '_' . $code]);
        $report = $this->cache->loadData();
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
        $this->cache->saveData(Json::encode($report));
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
