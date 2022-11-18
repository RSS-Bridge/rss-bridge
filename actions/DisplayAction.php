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
        $bridgeFactory = new BridgeFactory();

        $bridgeClassName = null;
        if (isset($request['bridge'])) {
            $bridgeClassName = $bridgeFactory->sanitizeBridgeName($request['bridge']);
        }

        if ($bridgeClassName === null) {
            throw new \InvalidArgumentException('Bridge name invalid!');
        }

        $format = $request['format'] ?? null;
        if (!$format) {
            throw new \Exception('You must specify a format!');
        }
        if (!$bridgeFactory->isWhitelisted($bridgeClassName)) {
            throw new \Exception('This bridge is not whitelisted');
        }

        $formatFactory = new FormatFactory();
        $format = $formatFactory->create($format);

        $bridge = $bridgeFactory->create($bridgeClassName);
        $bridge->loadConfiguration();

        $noproxy = array_key_exists('_noproxy', $request)
            && filter_var($request['_noproxy'], FILTER_VALIDATE_BOOLEAN);

        if (Configuration::getConfig('proxy', 'url') && Configuration::getConfig('proxy', 'by_bridge') && $noproxy) {
            define('NOPROXY', true);
        }

        if (array_key_exists('_cache_timeout', $request)) {
            if (! Configuration::getConfig('cache', 'custom_timeout')) {
                unset($request['_cache_timeout']);
                $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '?' . http_build_query($request);
                return new Response('', 301, ['Location' => $uri]);
            }

            $cache_timeout = filter_var($request['_cache_timeout'], FILTER_VALIDATE_INT);
        } else {
            $cache_timeout = $bridge->getCacheTimeout();
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

        $cacheFactory = new CacheFactory();

        $cache = $cacheFactory->create();
        $cache->setScope('');
        $cache->purgeCache(86400); // 24 hours
        $cache->setKey($cache_params);

        $items = [];
        $infos = [];
        $mtime = $cache->getTime();

        if (
            $mtime !== false
            && (time() - $cache_timeout < $mtime)
            && !Debug::isEnabled()
        ) {
            // Load cached data
            // Send "Not Modified" response if client supports it
            // Implementation based on https://stackoverflow.com/a/10847262
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $stime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

                if ($mtime <= $stime) {
                    // Cached data is older or same
                    $lastModified2 = gmdate('D, d M Y H:i:s ', $mtime) . 'GMT';
                    return new Response('', 304, ['Last-Modified' => $lastModified2]);
                }
            }

            $cached = $cache->loadData();

            if (isset($cached['items']) && isset($cached['extraInfos'])) {
                foreach ($cached['items'] as $item) {
                    $items[] = new FeedItem($item);
                }

                $infos = $cached['extraInfos'];
            }
        } else {
            // Collect new data
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

                $errorCount = logBridgeError($bridge::NAME, $e->getCode());

                if ($errorCount >= Configuration::getConfig('error', 'report_limit')) {
                    if (Configuration::getConfig('error', 'output') === 'feed') {
                        $item = new FeedItem();

                        // Create "new" error message every 24 hours
                        $request['_error_time'] = urlencode((int)(time() / 86400));

                        // todo: I don't think this _error_time in the title is useful. It's confusing.
                        $itemTitle = sprintf('Bridge returned error %s! (%s)', $e->getCode(), $request['_error_time']);
                        $item->setTitle($itemTitle);
                        $item->setURI(get_current_url());
                        $item->setTimestamp(time());

                        // todo: consider giving more helpful error messages
                        $content = render_template(__DIR__ . '/../templates/bridge-error.html.php', [
                            'error' => render_template(__DIR__ . '/../templates/error.html.php', ['e' => $e]),
                            'searchUrl' => self::createGithubSearchUrl($bridge),
                            'issueUrl' => self::createGithubIssueUrl($bridge, $e, create_sane_exception_message($e)),
                            'maintainer' => $bridge->getMaintainer(),
                        ]);
                        $item->setContent($content);

                        $items[] = $item;
                    } elseif (Configuration::getConfig('error', 'output') === 'http') {
                        throw $e;
                    }
                }
            }

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
