<?php

/**
 * Fetch data from an http url
 *
 * @param array $httpHeaders E.g. ['Content-type: text/plain']
 * @param array $curlOptions Associative array e.g. [CURLOPT_MAXREDIRS => 3]
 * @param bool $returnFull Whether to return an array: ['code' => int, 'headers' => array, 'content' => string]
 * @return string|array
 */
function getContents(
    string $url,
    array $httpHeaders = [],
    array $curlOptions = [],
    bool $returnFull = false
) {
    $httpClient = RssBridge::getHttpClient();

    $httpHeadersNormalized = [];
    foreach ($httpHeaders as $httpHeader) {
        $parts = explode(':', $httpHeader);
        $headerName = trim($parts[0]);
        $headerValue = trim(implode(':', array_slice($parts, 1)));
        $httpHeadersNormalized[$headerName] = $headerValue;
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
    $config = [
        'useragent' => Configuration::getConfig('http', 'useragent'),
        'timeout' => Configuration::getConfig('http', 'timeout'),
        'headers' => array_merge($defaultHttpHeaders, $httpHeadersNormalized),
        'curl_options' => $curlOptions,
    ];

    $maxFileSize = Configuration::getConfig('http', 'max_filesize');
    if ($maxFileSize) {
        // Convert from MB to B by multiplying with 2^20 (1M)
        $config['max_filesize'] = $maxFileSize * 2 ** 20;
    }

    if (Configuration::getConfig('proxy', 'url') && !defined('NOPROXY')) {
        $config['proxy'] = Configuration::getConfig('proxy', 'url');
    }

    $cache = RssBridge::getCache();
    $cacheKey = 'server_' . $url;

    /** @var Response $cachedResponse */
    $cachedResponse = $cache->get($cacheKey);
    if ($cachedResponse) {
        $cachedLastModified = $cachedResponse->getHeader('last-modified');
        if ($cachedLastModified) {
            $cachedLastModified = new \DateTimeImmutable($cachedLastModified);
            $config['if_not_modified_since'] = $cachedLastModified->getTimestamp();
        }
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
            $exceptionMessage = sprintf(
                '%s resulted in %s %s %s',
                $url,
                $response->getCode(),
                $response->getStatusLine(),
                // If debug, include a part of the response body in the exception message
                Debug::isEnabled() ? mb_substr($response->getBody(), 0, 500) : '',
            );

            if (CloudFlareException::isCloudFlareResponse($response)) {
                throw new CloudFlareException($exceptionMessage, $response->getCode());
            }
            throw new HttpException(trim($exceptionMessage), $response->getCode());
    }
    if ($returnFull === true) {
        // todo: return the actual response object
        return [
            'code'      => $response->getCode(),
            'headers'   => $response->getHeaders(),
            // For legacy reasons, use 'content' instead of 'body'
            'content'   => $response->getBody(),
        ];
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
 * @return false|simple_html_dom Contents as simplehtmldom object.
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
) {
    $content = getContents(
        $url,
        $header ?? [],
        $opts ?? []
    );
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

/**
 * Gets contents from the Internet as simplhtmldom object. Contents are cached
 * and re-used for subsequent calls until the cache duration elapsed.
 *
 * _Notice_: Cached contents are forcefully removed after 24 hours (86400 seconds).
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
    $cache = RssBridge::getCache();
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
