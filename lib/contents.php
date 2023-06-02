<?php

final class Response
{
    public const STATUS_CODES = [
        '100' => 'Continue',
        '101' => 'Switching Protocols',
        '200' => 'OK',
        '201' => 'Created',
        '202' => 'Accepted',
        '203' => 'Non-Authoritative Information',
        '204' => 'No Content',
        '205' => 'Reset Content',
        '206' => 'Partial Content',
        '300' => 'Multiple Choices',
        '301' => 'Moved Permanently',
        '302' => 'Found',
        '303' => 'See Other',
        '304' => 'Not Modified',
        '305' => 'Use Proxy',
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '402' => 'Payment Required',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '406' => 'Not Acceptable',
        '407' => 'Proxy Authentication Required',
        '408' => 'Request Timeout',
        '409' => 'Conflict',
        '410' => 'Gone',
        '411' => 'Length Required',
        '412' => 'Precondition Failed',
        '413' => 'Request Entity Too Large',
        '414' => 'Request-URI Too Long',
        '415' => 'Unsupported Media Type',
        '416' => 'Requested Range Not Satisfiable',
        '417' => 'Expectation Failed',
        '429' => 'Too Many Requests',
        '500' => 'Internal Server Error',
        '501' => 'Not Implemented',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
        '505' => 'HTTP Version Not Supported'
    ];
    private string $body;
    private int $code;
    private array $headers;

    public function __construct(
        string $body = '',
        int $code = 200,
        array $headers = []
    ) {
        $this->body = $body;
        $this->code = $code;
        $this->headers = $headers;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function send(): void
    {
        http_response_code($this->code);
        foreach ($this->headers as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
        print $this->body;
    }
}

/**
 * Fetch data from an http url
 *
 * @param array $httpHeaders E.g. ['Content-type: text/plain']
 * @param array $curlOptions Associative array e.g. [CURLOPT_MAXREDIRS => 3]
 * @param bool $returnFull Whether to return an array:
 *                         [
 *                              'code' => int,
 *                              'header' => array,
 *                              'content' => string,
 *                              'status_lines' => array,
 *                         ]

 * @return string|array
 */
function getContents(
    string $url,
    array $httpHeaders = [],
    array $curlOptions = [],
    bool $returnFull = false
) {
    $cacheFactory = new CacheFactory();

    $cache = $cacheFactory->create();
    $cache->setScope('server');
    $cache->purgeCache(86400); // 24 hours (forced)
    $cache->setKey([$url]);

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
    $httpHeadersNormalized = [];
    foreach ($httpHeaders as $httpHeader) {
        $parts = explode(':', $httpHeader);
        $headerName = trim($parts[0]);
        $headerValue = trim(implode(':', array_slice($parts, 1)));
        $httpHeadersNormalized[$headerName] = $headerValue;
    }
    $config = [
        'useragent' => Configuration::getConfig('http', 'useragent'),
        'timeout' => Configuration::getConfig('http', 'timeout'),
        'headers' => array_merge($defaultHttpHeaders, $httpHeadersNormalized),
        'curl_options' => $curlOptions,
    ];

    $maxFileSize = Configuration::getConfig('http', 'max_filesize');
    if ($maxFileSize) {
        // Multiply with 2^20 (1M) to the value in bytes
        $config['max_filesize'] = $maxFileSize * 2 ** 20;
    }

    if (Configuration::getConfig('proxy', 'url') && !defined('NOPROXY')) {
        $config['proxy'] = Configuration::getConfig('proxy', 'url');
    }
    if (!Debug::isEnabled() && $cache->getTime()) {
        $config['if_not_modified_since'] = $cache->getTime();
    }

    $result = _http_request($url, $config);
    $response = [
        'code' => $result['code'],
        'status_lines' => $result['status_lines'],
        'header' => $result['headers'],
        'content' => $result['body'],
    ];

    switch ($result['code']) {
        case 200:
        case 201:
        case 202:
            if (isset($result['headers']['cache-control'])) {
                $cachecontrol = $result['headers']['cache-control'];
                $lastValue = array_pop($cachecontrol);
                $directives = explode(',', $lastValue);
                $directives = array_map('trim', $directives);
                if (in_array('no-cache', $directives) || in_array('no-store', $directives)) {
                    // Don't cache as instructed by the server
                    break;
                }
            }
            $cache->saveData($result['body']);
            break;
        case 301:
        case 302:
        case 303:
            // todo: cache
            break;
        case 304:
            // Not Modified
            $response['content'] = $cache->loadData();
            break;
        default:
            $exceptionMessage = sprintf(
                '%s resulted in %s %s %s',
                $url,
                $result['code'],
                Response::STATUS_CODES[$result['code']] ?? '',
                // If debug, include a part of the response body in the exception message
                Debug::isEnabled() ? mb_substr($result['body'], 0, 500) : '',
            );

            // The following code must be extracted if it grows too much
            $cloudflareTitles = [
                '<title>Just a moment...',
                '<title>Please Wait...',
                '<title>Attention Required!',
                '<title>Security | Glassdoor',
            ];
            foreach ($cloudflareTitles as $cloudflareTitle) {
                if (str_contains($result['body'], $cloudflareTitle)) {
                    throw new CloudFlareException($exceptionMessage, $result['code']);
                }
            }

            throw new HttpException($exceptionMessage, $result['code']);
    }
    if ($returnFull === true) {
        return $response;
    }
    return $response['content'];
}

/**
 * Fetch content from url
 *
 * @internal Private function used internally
 * @throws HttpException
 */
function _http_request(string $url, array $config = []): array
{
    $defaults = [
        'useragent' => null,
        'timeout' => 5,
        'headers' => [],
        'proxy' => null,
        'curl_options' => [],
        'if_not_modified_since' => null,
        'retries' => 3,
        'max_filesize' => null,
    ];
    $config = array_merge($defaults, $config);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $httpHeaders = [];
    foreach ($config['headers'] as $name => $value) {
        $httpHeaders[] = sprintf('%s: %s', $name, $value);
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
    if ($config['useragent']) {
        curl_setopt($ch, CURLOPT_USERAGENT, $config['useragent']);
    }
    curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeout']);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

    if ($config['max_filesize']) {
        // This option inspects the Content-Length header
        curl_setopt($ch, CURLOPT_MAXFILESIZE, $config['max_filesize']);
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        // This progress function will monitor responses who omit the Content-Length header
        curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, function ($ch, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($config) {
            if ($downloaded > $config['max_filesize']) {
                // Return a non-zero value to abort the transfer
                return -1;
            }
            return 0;
        });
    }

    if ($config['proxy']) {
        curl_setopt($ch, CURLOPT_PROXY, $config['proxy']);
    }
    if (curl_setopt_array($ch, $config['curl_options']) === false) {
        throw new \Exception('Tried to set an illegal curl option');
    }

    if ($config['if_not_modified_since']) {
        curl_setopt($ch, CURLOPT_TIMEVALUE, $config['if_not_modified_since']);
        curl_setopt($ch, CURLOPT_TIMECONDITION, CURL_TIMECOND_IFMODSINCE);
    }

    $responseStatusLines = [];
    $responseHeaders = [];
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $rawHeader) use (&$responseHeaders, &$responseStatusLines) {
        $len = strlen($rawHeader);
        if ($rawHeader === "\r\n") {
            return $len;
        }
        if (preg_match('#^HTTP/(2|1.1|1.0)#', $rawHeader)) {
            $responseStatusLines[] = $rawHeader;
            return $len;
        }
        $header = explode(':', $rawHeader);
        if (count($header) === 1) {
            return $len;
        }
        $name = mb_strtolower(trim($header[0]));
        $value = trim(implode(':', array_slice($header, 1)));
        if (!isset($responseHeaders[$name])) {
            $responseHeaders[$name] = [];
        }
        $responseHeaders[$name][] = $value;
        return $len;
    });

    $attempts = 0;
    while (true) {
        $attempts++;
        $data = curl_exec($ch);
        if ($data !== false) {
            // The network call was successful, so break out of the loop
            break;
        }
        if ($attempts > $config['retries']) {
            // Finally give up
            throw new HttpException(sprintf(
                'cURL error %s: %s (%s) for %s',
                curl_error($ch),
                curl_errno($ch),
                'https://curl.haxx.se/libcurl/c/libcurl-errors.html',
                $url
            ));
        }
    }

    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [
        'code'      => $statusCode,
        'status_lines' => $responseStatusLines,
        'headers'   => $responseHeaders,
        'body'      => $data,
    ];
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
 * @param int $duration Cache duration in seconds.
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
    $duration = 86400,
    $header = [],
    $opts = [],
    $lowercase = true,
    $forceTagsClosed = true,
    $target_charset = DEFAULT_TARGET_CHARSET,
    $stripRN = true,
    $defaultBRText = DEFAULT_BR_TEXT,
    $defaultSpanText = DEFAULT_SPAN_TEXT
) {
    Logger::debug(sprintf('Caching url %s, duration %d', $url, $duration));

    // Initialize cache
    $cacheFactory = new CacheFactory();

    $cache = $cacheFactory->create();
    $cache->setScope('pages');
    $cache->purgeCache(86400); // 24 hours (forced)

    $params = [$url];
    $cache->setKey($params);

    // Determine if cached file is within duration
    $time = $cache->getTime();
    if (
        $time !== false
        && (time() - $duration < $time)
        && !Debug::isEnabled()
    ) {
        // Contents within duration and debug mode is disabled
        $content = $cache->loadData();
    } else {
        // Contents not within duration, or debug mode is enabled
        $content = getContents(
            $url,
            $header ?? [],
            $opts ?? []
        );
        // todo: fix bad if statement
        if ($content !== false) {
            $cache->saveData($content);
        }
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
