<?php

declare(strict_types=1);

final class UrlException extends \Exception
{
}

final class Url
{
    private string $scheme;
    private string $host;
    private string $path;
    private ?string $queryString;

    private function __construct()
    {
    }

    public static function fromString(string $url): self
    {
        $parts = parse_url($url);
        if ($parts === false) {
            throw new UrlException(sprintf('Invalid url %s', $url));
        }

        return (new self())
            ->withScheme($parts['scheme'] ?? '')
            ->withHost($parts['host'])
            ->withPath($parts['path'] ?? '/')
            ->withQueryString($parts['query'] ?? null);
    }

    public function getScheme(): string
    {
        return $this->scheme;
    }

    public function getHost(): string
    {
        return $this->host;
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
        return sprintf(
            '%s://%s%s%s',
            $this->scheme,
            $this->host,
            $this->path,
            $this->queryString ? '?' . $this->queryString : ''
        );
    }
}
