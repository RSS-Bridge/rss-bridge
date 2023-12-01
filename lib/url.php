<?php

declare(strict_types=1);

final class UrlException extends \Exception
{
}

/**
 * Intentionally restrictive url parser.
 *
 * Only absolute http/https urls.
 */
final class Url
{
    private string $scheme;
    private string $host;
    private int $port;
    private string $path;
    private ?string $queryString;

    private function __construct()
    {
    }

    public static function fromString(string $url): self
    {
        if (!self::validate($url)) {
            throw new UrlException(sprintf('Illegal url: "%s"', $url));
        }

        $parts = parse_url($url);
        if ($parts === false) {
            throw new UrlException(sprintf('Failed to parse_url(): %s', $url));
        }

        return (new self())
            ->withScheme($parts['scheme'] ?? '')
            ->withHost($parts['host'])
            ->withPort($parts['port'] ?? 80)
            ->withPath($parts['path'] ?? '/')
            ->withQueryString($parts['query'] ?? null);
            // todo: add fragment
    }

    public static function validate(string $url): bool
    {
        if (strlen($url) > 1500) {
            return false;
        }
        $pattern = '#^https?://'   // scheme
            . '([a-z0-9-]+\.?)+'   // one or more domain names
            . '(\.[a-z]{1,24})?'   // optional global tld
            . '(:\d+)?'            // optional port
            . '($|/|\?)#i';        // end of string or slash or question mark

        return preg_match($pattern, $url) === 1;
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }

    public function withScheme(string $scheme): self
    {
        if (!in_array($scheme, ['http', 'https'])) {
            throw new UrlException(sprintf('Invalid scheme %s', $scheme));
        }
        $clone = clone $this;
        $clone->scheme = $scheme;
        return $clone;
    }

    public function withHost(string $host): self
    {
        $clone = clone $this;
        $clone->host = $host;
        return $clone;
    }

    public function withPort(int $port)
    {
        $clone = clone $this;
        $clone->port = $port;
        return $clone;
    }

    public function withPath(string $path): self
    {
        if (!str_starts_with($path, '/')) {
            throw new UrlException(sprintf('Path must start with forward slash: %s', $path));
        }
        $clone = clone $this;
        $clone->path = $path;
        return $clone;
    }

    public function withQueryString(?string $queryString): self
    {
        $clone = clone $this;
        $clone->queryString = $queryString;
        return $clone;
    }

    public function __toString()
    {
        if ($this->port === 80) {
            $port = '';
        } else {
            $port = ':' . $this->port;
        }
        if ($this->queryString) {
            $queryString = '?' . $this->queryString;
        } else {
            $queryString = '';
        }

        return sprintf(
            '%s://%s%s%s%s',
            $this->scheme,
            $this->host,
            $port,
            $this->path,
            $queryString
        );
    }
}
