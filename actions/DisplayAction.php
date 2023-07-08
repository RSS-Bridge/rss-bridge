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

class DisplayAction implements ActionInterface
{
    public function execute(array $request)
    {
        if (Configuration::getConfig('system', 'enable_maintenance_mode')) {
            return new Response('503 Service Unavailable', 503);
        }

        $bridgeFactory = new BridgeFactory();

        $bridgeClassName = $bridgeFactory->createBridgeClassName($request['bridge'] ?? '');

        $format = $request['format'] ?? null;
        if (!$format) {
            throw new \Exception('You must specify a format!');
        }
        if (!$bridgeFactory->isEnabled($bridgeClassName)) {
            throw new \Exception('This bridge is not whitelisted');
        }

        $formatFactory = new FormatFactory();
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

        $cache = RssBridge::getCache();
        $cache->setScope('');
        $cache->setKey($cache_params);
        // This cache purge will basically delete all cache items older than 24h, regardless of scope and key
        $cache->purgeCache(86400);

        $items = [];
        $infos = [];
        $mtime = $cache->getTime();

        if (
            $mtime
            && (time() - $cacheTimeout < $mtime)
            && !Debug::isEnabled()
        ) {
            // At this point we found the feed in the cache and debug mode is disabled

            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                // The client wants to know if the feed has changed since its last check
                $stime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
                if ($mtime <= $stime) {
                    $lastModified2 = gmdate('D, d M Y H:i:s ', $mtime) . 'GMT';
                    return new Response('', 304, ['Last-Modified' => $lastModified2]);
                }
            }

            // Load the feed from cache and prepare it
            $cached = $cache->loadData();
            if (isset($cached['items']) && isset($cached['extraInfos'])) {
                foreach ($cached['items'] as $item) {
                    $items[] = new FeedItem($item);
                }
                $infos = $cached['extraInfos'];
            }
        } else {
            // At this point we did NOT find the feed in the cache or debug mode is enabled.
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
            } catch (\Throwable $e) {
                if ($e instanceof HttpException) {
                    // Produce a smaller log record for http exceptions
                    Logger::warning(sprintf('Exception in %s: %s', $bridgeClassName, create_sane_exception_message($e)));
                } else {
                    // Log the exception
                    Logger::error(sprintf('Exception in %s', $bridgeClassName), ['e' => $e]);
                }

                // Emit error only if we are passed the error report limit
                $errorCount = self::logBridgeError($bridge->getName(), $e->getCode());
                if ($errorCount >= Configuration::getConfig('error', 'report_limit')) {
                    if (Configuration::getConfig('error', 'output') === 'feed') {
                        // Emit the error as a feed item in a feed so that feed readers can pick it up
                        $items[] = $this->createFeedItemFromException($e, $bridge);
                    } elseif (Configuration::getConfig('error', 'output') === 'http') {
                        // Emit as a regular web response
                        throw $e;
                    }
                }
            }

            // Unfortunately need to set scope and key again because they might be modified
            $cache->setScope('');
            $cache->setKey($cache_params);
            $cache->saveData([
                'items' => array_map(function (FeedItem $item) {
                    return $item->toArray();
                }, $items),
                'extraInfos' => $infos
            ]);
        }

        $format->setItems($items);
        $format->setExtraInfos($infos);
        $lastModified = $cache->getTime();
        $format->setLastModified($lastModified);
        $headers = [];
        if ($lastModified) {
            $headers['Last-Modified'] = gmdate('D, d M Y H:i:s ', $lastModified) . 'GMT';
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

    private static function logBridgeError($bridgeName, $code)
    {
        $cache = RssBridge::getCache();
        $cache->setScope('error_reporting');
        $cache->setkey([$bridgeName . '_' . $code]);

        if ($report = $cache->loadData()) {
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
        $cache->saveData(Json::encode($report));
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
