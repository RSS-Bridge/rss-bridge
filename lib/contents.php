<?php

/**
 * Fetch data from an http url
 *
 * @param array $httpHeaders E.g. ['Content-type: text/plain']
 * @param array $curlOptions Associative array e.g. [CURLOPT_MAXREDIRS => 3]
 * @param bool $returnFull Whether to return Response object
 * @return string|Response
 */
function getContents(
    string $url,
    array $httpHeaders = [],
    array $curlOptions = [],
    bool $returnFull = false
) {
    global $container;

    /** @var HttpClient $httpClient */
    $httpClient = $container['http_client'];

    /** @var CacheInterface $cache */
    $cache = $container['cache'];

    // TODO: consider url validation at this point

    $config = [
        'useragent'     => Configuration::getConfig('http', 'useragent'),
        'timeout'       => Configuration::getConfig('http', 'timeout'),
        'retries'       => Configuration::getConfig('http', 'retries'),
        'curl_options'  => $curlOptions,
    ];

    $httpHeadersNormalized = [];
    foreach ($httpHeaders as $httpHeader) {
        $parts = explode(':', $httpHeader);
        $headerName = trim($parts[0]);
        $headerValue = trim(implode(':', array_slice($parts, 1)));
        $httpHeadersNormalized[$headerName] = $headerValue;
    }

    $requestBodyHash = null;
    if (isset($curlOptions[CURLOPT_POSTFIELDS])) {
        $requestBodyHash = md5(Json::encode($curlOptions[CURLOPT_POSTFIELDS], false));
    }
    $cacheKey = implode('_', ['server',  $url, $requestBodyHash]);

    /** @var Response $cachedResponse */
    $cachedResponse = $cache->get($cacheKey);
    if ($cachedResponse) {
        $lastModified = $cachedResponse->getHeader('last-modified');
        if ($lastModified) {
            try {
                // Some servers send Unix timestamp instead of RFC7231 date. Prepend it with @ to allow parsing as DateTime
                $lastModified = new \DateTimeImmutable((is_numeric($lastModified) ? '@' : '') . $lastModified);
                $config['if_not_modified_since'] = $lastModified->getTimestamp();
            } catch (Exception $e) {
                // Failed to parse last-modified
            }
        }
        $etag = $cachedResponse->getHeader('etag');
        if ($etag) {
            $httpHeadersNormalized['if-none-match'] = $etag;
        }
    }

    // Snagged from https://github.com/lwthiker/curl-impersonate/blob/main/firefox/curl_ff102
    $defaultHttpHeaders = [
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Accept-Language' => 'en-US,en;q=0.5',
        'Upgrade-Insecure-Requests' => '1',
        'Sec-Fetch-Dest' => 'document',
        'Sec-Fetch-Mode' => 'navigate',
        'Sec-Fetch-Site' => 'none',
        'Sec-Fetch-User' => '?1',
        'TE' => 'trailers',
    ];

    $config['headers'] = array_merge($defaultHttpHeaders, $httpHeadersNormalized);

    $maxFileSize = Configuration::getConfig('http', 'max_filesize');
    if ($maxFileSize) {
        // Convert from MB to B by multiplying with 2^20 (1M)
        $config['max_filesize'] = $maxFileSize * 2 ** 20;
    }

    if (Configuration::getConfig('proxy', 'url') && !defined('NOPROXY')) {
        $config['proxy'] = Configuration::getConfig('proxy', 'url');
    }

    $response = $httpClient->request($url, $config);

    switch ($response->getCode()) {
        case 200:
        case 201:
        case 202:
            $cacheControl = $response->getHeader('cache-control');
            if ($cacheControl) {
                $directives = explode(',', $cacheControl);
                $directives = array_map('trim', $directives);
                if (in_array('no-cache', $directives) || in_array('no-store', $directives)) {
                    // Don't cache as instructed by the server
                    break;
                }
            }
            $cache->set($cacheKey, $response, 86400 * 10);
            break;
        case 301:
        case 302:
        case 303:
            // todo: cache
            break;
        case 304:
            // Not Modified
            $response = $response->withBody($cachedResponse->getBody());
            break;
        default:
            $e = HttpException::fromResponse($response, $url);
            throw $e;
    }
    if ($returnFull === true) {
        return $response;
    }
    return $response->getBody();
}

/**
 * Gets contents from the Internet as simplhtmldom object.
 *
 * @param string $url The URL.
 * @param array $header (optional) A list of cURL header.
 * For more information follow the links below.
 * * https://php.net/manual/en/function.curl-setopt.php
 * * https://curl.haxx.se/libcurl/c/CURLOPT_HTTPHEADER.html
 * @param array $opts (optional) A list of cURL options as associative array in
 * the format `$opts[$option] = $value;`, where `$option` is any `CURLOPT_XXX`
 * option and `$value` the corresponding value.
 *
 * For more information see http://php.net/manual/en/function.curl-setopt.php
 * @param bool $lowercase Force all selectors to lowercase.
 * @param bool $forceTagsClosed Forcefully close tags in malformed HTML.
 *
 * _Remarks_: Forcefully closing tags is great for malformed HTML, but it can
 * lead to parsing errors.
 * @param string $target_charset Defines the target charset.
 * @param bool $stripRN Replace all occurrences of `"\r"` and `"\n"` by `" "`.
 * @param string $defaultBRText Specifies the replacement text for `<br>` tags
 * when returning plaintext.
 * @param string $defaultSpanText Specifies the replacement text for `<span />`
 * tags when returning plaintext.
 */
function getSimpleHTMLDOM(
    $url,
    $header = [],
    $opts = [],
    $lowercase = true,
    $forceTagsClosed = true,
    $target_charset = DEFAULT_TARGET_CHARSET,
    $stripRN = true,
    $defaultBRText = DEFAULT_BR_TEXT,
    $defaultSpanText = DEFAULT_SPAN_TEXT
): \simple_html_dom {
    $html = getContents($url, $header ?? [], $opts ?? []);
    if ($html === '') {
        throw new \Exception('Unable to parse dom because the http response was the empty string');
    }

    return str_get_html(
        $html,
        $lowercase,
        $forceTagsClosed,
        $target_charset,
        $stripRN,
        $defaultBRText,
        $defaultSpanText
    );
}

/**
 * Fetch contents from the Internet as simplhtmldom object. Contents are cached
 * and re-used for subsequent calls until the cache duration elapsed.
 *
 * @param string $url The URL.
 * @param int $ttl Cache duration in seconds.
 * @param array $header (optional) A list of cURL header.
 * For more information follow the links below.
 * * https://php.net/manual/en/function.curl-setopt.php
 * * https://curl.haxx.se/libcurl/c/CURLOPT_HTTPHEADER.html
 * @param array $opts (optional) A list of cURL options as associative array in
 * the format `$opts[$option] = $value;`, where `$option` is any `CURLOPT_XXX`
 * option and `$value` the corresponding value.
 *
 * For more information see http://php.net/manual/en/function.curl-setopt.php
 * @param bool $lowercase Force all selectors to lowercase.
 * @param bool $forceTagsClosed Forcefully close tags in malformed HTML.
 *
 * _Remarks_: Forcefully closing tags is great for malformed HTML, but it can
 * lead to parsing errors.
 * @param string $target_charset Defines the target charset.
 * @param bool $stripRN Replace all occurrences of `"\r"` and `"\n"` by `" "`.
 * @param string $defaultBRText Specifies the replacement text for `<br>` tags
 * when returning plaintext.
 * @param string $defaultSpanText Specifies the replacement text for `<span />`
 * tags when returning plaintext.
 * @return false|simple_html_dom Contents as simplehtmldom object.
 */
function getSimpleHTMLDOMCached(
    $url,
    $ttl = 86400,
    $header = [],
    $opts = [],
    $lowercase = true,
    $forceTagsClosed = true,
    $target_charset = DEFAULT_TARGET_CHARSET,
    $stripRN = true,
    $defaultBRText = DEFAULT_BR_TEXT,
    $defaultSpanText = DEFAULT_SPAN_TEXT
) {
    global $container;

    /** @var CacheInterface $cache */
    $cache = $container['cache'];

    $cacheKey = 'pages_' . $url;
    $content = $cache->get($cacheKey);
    if (!$content) {
        $content = getContents($url, $header ?? [], $opts ?? []);
        $cache->set($cacheKey, $content, $ttl);
    }
    return str_get_html(
        $content,
        $lowercase,
        $forceTagsClosed,
        $target_charset,
        $stripRN,
        $defaultBRText,
        $defaultSpanText
    );
}
