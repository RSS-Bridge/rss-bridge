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
        $bridgeFactory = new \BridgeFactory();

        $bridgeClassName = null;
        if (isset($request['bridge'])) {
            $bridgeClassName = $bridgeFactory->sanitizeBridgeName($request['bridge']);
        }

        if ($bridgeClassName === null) {
            throw new \InvalidArgumentException('Bridge name invalid!');
        }

        $format = $request['format']
            or returnClientError('You must specify a format!');

        // whitelist control
        if (!$bridgeFactory->isWhitelisted($bridgeClassName)) {
            throw new \Exception('This bridge is not whitelisted', 401);
            die;
        }

        // Data retrieval
        $bridge = $bridgeFactory->create($bridgeClassName);
        $bridge->loadConfiguration();

        $noproxy = array_key_exists('_noproxy', $request)
            && filter_var($request['_noproxy'], FILTER_VALIDATE_BOOLEAN);

        if (Configuration::getConfig('proxy', 'url') && Configuration::getConfig('proxy', 'by_bridge') && $noproxy) {
            define('NOPROXY', true);
        }

        // Cache timeout
        $cache_timeout = -1;
        if (array_key_exists('_cache_timeout', $request)) {
            if (!CUSTOM_CACHE_TIMEOUT) {
                unset($request['_cache_timeout']);
                $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) . '?' . http_build_query($request);
                header('Location: ' . $uri, true, 301);
                exit;
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

        // Initialize cache
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
        ) { // Load cached data
            // Send "Not Modified" response if client supports it
            // Implementation based on https://stackoverflow.com/a/10847262
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
                $stime = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);

                if ($mtime <= $stime) { // Cached data is older or same
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', $mtime) . 'GMT', true, 304);
                    exit;
                }
            }

            $cached = $cache->loadData();

            if (isset($cached['items']) && isset($cached['extraInfos'])) {
                foreach ($cached['items'] as $item) {
                    $items[] = new \FeedItem($item);
                }

                $infos = $cached['extraInfos'];
            }
        } else { // Collect new data
            try {
                $bridge->setDatas($bridge_params);
                $bridge->collectData();

                $items = $bridge->getItems();

                // Transform "legacy" items to FeedItems if necessary.
                // Remove this code when support for "legacy" items ends!
                if (isset($items[0]) && is_array($items[0])) {
                    $feedItems = [];

                    foreach ($items as $item) {
                        $feedItems[] = new \FeedItem($item);
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
                error_log($e);

                if (logBridgeError($bridge::NAME, $e->getCode()) >= Configuration::getConfig('error', 'report_limit')) {
                    if (Configuration::getConfig('error', 'output') === 'feed') {
                        $item = new \FeedItem();

                        // Create "new" error message every 24 hours
                        $request['_error_time'] = urlencode((int)(time() / 86400));

                        $message = sprintf(
                            'Bridge returned error %s! (%s)',
                            $e->getCode(),
                            $request['_error_time']
                        );
                        $item->setTitle($message);

                        $item->setURI(
                            (isset($_SERVER['REQUEST_URI']) ? parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '')
                            . '?'
                            . http_build_query($request)
                        );

                        $item->setTimestamp(time());

                        $message = sprintf(
                            'Uncaught Exception %s: %s at %s line %s',
                            get_class($e),
                            $e->getMessage(),
                            trim_path_prefix($e->getFile()),
                            $e->getLine()
                        );

                        $content = render_template('bridge-error.html.php', [
                            'message' => $message,
                            'stacktrace' => create_sane_stacktrace($e),
                            'searchUrl' => self::createGithubSearchUrl($bridge),
                            'issueUrl' => self::createGithubIssueUrl($bridge, $e, $message),
                            'bridge' => $bridge,
                        ]);
                        $item->setContent($content);

                        $items[] = $item;
                    } elseif (Configuration::getConfig('error', 'output') === 'http') {
                        throw $e;
                    }
                }
            }

            $cache->saveData([
                'items' => array_map(function ($i) {
                    return $i->toArray();
                }, $items),
                'extraInfos' => $infos
            ]);
        }

        $formatFactory = new FormatFactory();
        $format = $formatFactory->create($format);
        $format->setItems($items);
        $format->setExtraInfos($infos);
        $lastModified = $cache->getTime();
        $format->setLastModified($lastModified);
        if ($lastModified) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s ', $lastModified) . 'GMT');
        }
        header('Content-Type: ' . $format->getMimeType() . '; charset=' . $format->getCharset());
        print $format->stringify();
    }

    private static function createGithubIssueUrl($bridge, $e, string $message): string
    {
        return sprintf('https://github.com/RSS-Bridge/rss-bridge/issues/new?%s', http_build_query([
            'title' => sprintf('%s failed with error %s', $bridge->getName(), $e->getCode()),
            'body' => sprintf(
                "```\n%s\n\n%s\n\nQuery string:%s\nVersion:%s\n```",
                $message,
                implode("\n", create_sane_stacktrace($e)),
                $_SERVER['QUERY_STRING'] ?? '',
                Configuration::getVersion(),
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
