<?php

/**
 * Thrown by bridges
 */
final class RateLimitException extends \Exception
{
}

/**
 * @internal Do not use this class in bridges
 */
class HttpException extends \Exception
{
    public ?Response $response;

    public function __construct(string $message = '', int $statusCode = 0, ?Response $response = null)
    {
        parent::__construct($message, $statusCode);
        $this->response = $response ?? new Response('', 0);
    }

    public static function fromResponse(Response $response, string $url): HttpException
    {
        $message = sprintf(
            '%s resulted in %s %s %s',
            $url,
            $response->getCode(),
            $response->getStatusLine(),
            // If debug, include a part of the response body in the exception message
            Debug::isEnabled() ? mb_substr($response->getBody(), 0, 500) : '',
        );
        if (CloudFlareException::isCloudFlareResponse($response)) {
            return new CloudFlareException($message, $response->getCode(), $response);
        }
        return new HttpException(trim($message), $response->getCode(), $response);
    }
}

final class CloudFlareException extends HttpException
{
    public static function isCloudFlareResponse(Response $response): bool
    {
        $cloudflareTitles = [
            '<title>Just a moment...',
            '<title>Please Wait...',
            '<title>Attention Required!',
            '<title>Security | Glassdoor',
            '<title>Access denied</title>', // cf as seen on patreon.com
        ];
        foreach ($cloudflareTitles as $cloudflareTitle) {
            if (str_contains($response->getBody(), $cloudflareTitle)) {
                return true;
            }
        }
        return false;
    }
}

interface HttpClient
{
    public function request(string $url, array $config = []): Response;
}

final class CurlHttpClient implements HttpClient
{
    public function request(string $url, array $config = []): Response
    {
        $defaults = [
            'useragent' => null,
            'timeout' => 5,
            'headers' => [],
            'proxy' => null,
            'curl_options' => [],
            'if_not_modified_since' => null,
            'retries' => 2,
            'max_filesize' => null,
            'max_redirections' => 5,
        ];
        $config = array_merge($defaults, $config);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, $config['max_redirections']);
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
                $responseStatusLines[] = trim($rawHeader);
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

        // This retry logic is a bit hard to understand, but it works
        $tries = 0;
        while (true) {
            $tries++;
            $body = curl_exec($ch);
            if ($body !== false) {
                // The network call was successful, so break out of the loop
                break;
            }
            if ($tries <= $config['retries']) {
                continue;
            }
            // Max retries reached, give up
            $curl_error = curl_error($ch);
            $curl_errno = curl_errno($ch);
            throw new HttpException(sprintf(
                'cURL error %s: %s (%s) for %s',
                $curl_error,
                $curl_errno,
                'https://curl.haxx.se/libcurl/c/libcurl-errors.html',
                $url
            ));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return new Response($body, $statusCode, $responseHeaders);
    }
}

final class Request
{
    private array $get;
    private array $server;
    private array $attributes;

    private function __construct()
    {
    }

    public static function fromGlobals(): self
    {
        $self = new self();
        $self->get = $_GET;
        $self->server = $_SERVER;
        $self->attributes = [];
        return $self;
    }

    public static function fromCli(array $cliArgs): self
    {
        $self = new self();
        $self->get = $cliArgs;
        return $self;
    }

    public function get(string $key, $default = null): ?string
    {
        return $this->get[$key] ?? $default;
    }

    public function server(string $key, string $default = null): ?string
    {
        return $this->server[$key] ?? $default;
    }

    public function withAttribute(string $name, $value = true): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    public function attribute(string $key, $default = null)
    {
        return $this->attributes[$key] ?? $default;
    }

    public function toArray(): array
    {
        return $this->get;
    }

    public function tryDecryptUrl(): void
    {
        $urlEncryptionService = UrlEncryptionService::fromRequest($this);
        if (!$urlEncryptionService) {
            throw new \Exception('The encrypted URL token is not valid.');
        }

        $this->get = $urlEncryptionService->toArray();
    }
}

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
        $this->headers = [];

        foreach ($headers as $name => $value) {
            $name = mb_strtolower($name);
            if (!isset($this->headers[$name])) {
                $this->headers[$name] = [];
            }
            if (is_string($value)) {
                $this->headers[$name][] = $value;
            }
            if (is_array($value)) {
                $this->headers[$name] = $value;
            }
        }
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getStatusLine(): string
    {
        return self::STATUS_CODES[$this->code] ?? '';
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * HTTP response may have multiple headers with the same name.
     *
     * This method by default, returns only the last header.
     *
     * @return string[]|string|null
     */
    public function getHeader(string $name, bool $all = false)
    {
        $name = mb_strtolower($name);
        $header = $this->headers[$name] ?? null;
        if (!$header) {
            return null;
        }
        if ($all) {
            return $header;
        }
        return array_pop($header);
    }

    public function withHeader(string $name, string $value): self
    {
        $clone = clone $this;
        $clone->headers[$name] = [$value];
        return $clone;
    }

    public function withBody(string $body): self
    {
        $clone = clone $this;
        $clone->body = $body;
        return $clone;
    }

    public function send(): void
    {
        http_response_code($this->code);
        foreach ($this->headers as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value));
            }
        }
        print $this->body;
    }
}
