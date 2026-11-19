<?php

declare(strict_types=1);

function fetch_dns_record(string $host): array
{
    $record = \dns_get_record($host, DNS_A);

    if ($record === false) {
        throw new \Exception(sprintf('Failed to fetch dns record: %s', $host));
    }
    if ($record === []) {
        throw new \Exception(sprintf('Could not resolve host: %s', $host));
    }

    return $record;
}

class Ipv4Address
{
    private string $ip;

    private function __construct()
    {
    }

    public static function fromString(string $ip): self
    {
        if (! static::validate($ip)) {
            throw new Exception('Not a valid ipv4 address: ' . $ip);
        }

        $self = new self();
        $self->ip = $ip;

        return $self;
    }

    public static function validate(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function isPublic(): bool
    {
        // TODO: migrate to FILTER_FLAG_GLOBAL_RANGE in php 8.2
        return !$this->isPrivate() && !$this->isReserved();
    }

    public function isPrivate(): bool
    {
        return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    public function isReserved(): bool
    {
        return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false;
    }

    public function __toString()
    {
        return $this->ip;
    }
}
