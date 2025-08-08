<?php

class DisplayAction implements ActionInterface
{
    private CacheInterface $cache;
    private Logger $logger;
    private BridgeFactory $bridgeFactory;

    public function __construct(
        CacheInterface $cache,
        Logger $logger,
        BridgeFactory $bridgeFactory
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->bridgeFactory = $bridgeFactory;
    }

    public function __invoke(Request $request): Response
    {
        $bridgeName = $request->get('bridge');
        $format = $request->get('format');
        $noproxy = $request->get('_noproxy');

        if (!$bridgeName) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'Missing bridge name parameter']), 400);
        }
        $bridgeClassName = $this->bridgeFactory->createBridgeClassName($bridgeName);
        if (!$bridgeClassName) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'Bridge not found']), 404);
        }

        if (!$format) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'You must specify a format']), 400);
        }
        if (!$this->bridgeFactory->isEnabled($bridgeClassName)) {
            return new Response(render(__DIR__ . '/../templates/error.html.php', ['message' => 'This bridge is not whitelisted']), 400);
        }

        // Disable proxy (if enabled and per user's request)
        if (
            Configuration::getConfig('proxy', 'url')
            && Configuration::getConfig('proxy', 'by_bridge')
            && $noproxy
        ) {
            // This const is only used once in getContents()
            define('NOPROXY', true);
        }

        $cacheKey = 'http_' . json_encode($request->toArray());

        $bridge = $this->bridgeFactory->create($bridgeClassName);

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

        return $response;
    }

    private function createResponse(Request $request, BridgeAbstract $bridge, string $format)
    {
        $items = [];

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
        } catch (\Throwable $e) {
            if ($e instanceof ClientException) {
                $this->logger->debug(sprintf('Exception in DisplayAction(%s): %s', $bridge->getShortName(), create_sane_exception_message($e)));
            } elseif ($e instanceof RateLimitException) {
                $this->logger->debug(sprintf('Exception in DisplayAction(%s): %s', $bridge->getShortName(), create_sane_exception_message($e)));
                return new Response(render(__DIR__ . '/../templates/exception.html.php', ['e' => $e]), 429);
            } elseif ($e instanceof HttpException) {
                if (in_array($e->getCode(), [429, 503])) {
                    // Log with debug, immediately reproduce and return
                    $this->logger->debug(sprintf('Exception in DisplayAction(%s): %s', $bridge->getShortName(), create_sane_exception_message($e)));
                    return new Response(render(__DIR__ . '/../templates/exception.html.php', ['e' => $e]), $e->getCode());
                }
                // Some other status code which we let fail normally (but don't log it)
            } else {
                $this->logger->error(sprintf('Exception in DisplayAction(%s)', $bridge->getShortName()), ['e' => $e]);
            }
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
                    $items = [$this->createFeedItemFromException($e, $bridge)];
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
        $format->setFeed($bridge->getFeed());
        $now = time();
        $format->setLastModified($now);
        $headers = [
            'last-modified' => gmdate('D, d M Y H:i:s ', $now) . 'GMT',
            'content-type'  => $format->getMimeType() . '; charset=UTF-8',
        ];
        $body = $format->render();

        // This is supposed to remove non-utf8 byte sequences, but I'm unsure if it works
        ini_set('mbstring.substitute_character', 'none');
        $body = mb_convert_encoding($body, 'UTF-8', 'UTF-8');

        return new Response($body, 200, $headers);
    }

    private function createFeedItemFromException($e, BridgeAbstract $bridge): array
    {
        $item = [];

        // Create a unique identifier every 24 hours
        $uniqueIdentifier = urlencode((int)(time() / 86400));
        $title = sprintf('Bridge returned error %s! (%s)', $e->getCode(), $uniqueIdentifier);

        $item['title'] = $title;
        $item['uri'] = get_current_url();
        $item['timestamp'] = time();

        // Create an item identifier for feed readers e.g. "staysafetv twitch videos_19389"
        $item['uid'] = $bridge->getName() . '_' . $uniqueIdentifier;

        $content = render_template(__DIR__ . '/../templates/bridge-error.html.php', [
            'error' => render_template(__DIR__ . '/../templates/exception.html.php', ['e' => $e]),
            'searchUrl' => self::createGithubSearchUrl($bridge),
            'issueUrl' => self::createGithubIssueUrl($bridge, $e),
            'maintainer' => $bridge->getMaintainer(),
        ]);
        $item['content'] = $content;

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

    private static function createGithubIssueUrl(BridgeAbstract $bridge, \Throwable $e): string
    {
        $maintainer = $bridge->getMaintainer();
        if (str_contains($maintainer, ',')) {
            $maintainers = explode(',', $maintainer);
        } else {
            $maintainers = [$maintainer];
        }
        $maintainers = array_map('trim', $maintainers);

        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $query = [
            'title' => $bridge->getName() . ' failed with: ' . $e->getMessage(),
            'body' => sprintf(
                "```\n%s\n\n%s\n\nQuery string: %s\nVersion: %s\nOs: %s\nPHP version: %s\n```\nMaintainer: @%s",
                create_sane_exception_message($e),
                implode("\n", trace_to_call_points(trace_from_exception($e))),
                $queryString,
                Configuration::getVersion(),
                PHP_OS_FAMILY,
                phpversion() ?: 'Unknown',
                implode(', @', $maintainers),
            ),
            'labels' => 'Bridge-Broken',
            'assignee' => $maintainer[0],
        ];

        return 'https://github.com/RSS-Bridge/rss-bridge/issues/new?' . http_build_query($query);
    }

    private static function createGithubSearchUrl($bridge): string
    {
        return sprintf(
            'https://github.com/RSS-Bridge/rss-bridge/issues?q=%s',
            urlencode('is:issue is:open ' . $bridge->getName())
        );
    }
}
