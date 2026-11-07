<?php

declare(strict_types=1);

function fetch_dns_record(string $host): array
{
    $record = \dns_get_record($host, DNS_A | DNS_AAAA);

    if ($record === false) {
        throw new \Exception(sprintf('Failed to fetch dns record: %s', $host));
    }
    if ($record === []) {
        throw new \Exception(sprintf('Could not resolve host: %s', $host));
    }

    return $record;
}

/**
 * Pick the address to connect to from the records returned by dns_get_record().
 *
 * Ipv4 is preferred. Ipv6 is only used for hosts which have no A records.
 */
function pick_dns_address(array $records): IpAddress
{
    $ipv4 = [];
    $ipv6 = [];
    foreach ($records as $record) {
        if (isset($record['ip'])) {
            $ipv4[] = $record['ip'];
        } elseif (isset($record['ipv6'])) {
            $ipv6[] = $record['ipv6'];
        }
    }

    if ($ipv4) {
        return Ipv4Address::fromString($ipv4[array_rand($ipv4)]);
    }
    if ($ipv6) {
        return Ipv6Address::fromString($ipv6[array_rand($ipv6)]);
    }

    throw new \Exception('No A or AAAA records found');
}

abstract class IpAddress
{
    protected const LABEL = 'ip';

    protected string $ip;

    protected function __construct(string $ip)
    {
        $this->ip = $ip;
    }

    public static function fromString(string $ip): self
    {
        if (! static::validate($ip)) {
            throw new Exception(sprintf('Not a valid %s address: %s', static::LABEL, $ip));
        }

        return new static($ip);
    }

    abstract public static function validate(string $ip): bool;

    abstract public function isPrivate(): bool;

    abstract public function isReserved(): bool;

    public function isPublic(): bool
    {
        // TODO: migrate to FILTER_FLAG_GLOBAL_RANGE in php 8.2
        return !$this->isPrivate() && !$this->isReserved();
    }

    public function __toString()
    {
        return $this->ip;
    }
}

class Ipv4Address extends IpAddress
{
    protected const LABEL = 'ipv4';

    public static function validate(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function isPrivate(): bool
    {
        return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    public function isReserved(): bool
    {
        return filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false;
    }
}

class Ipv6Address extends IpAddress
{
    protected const LABEL = 'ipv6';

    /**
     * Ranges whose last 32 bits hold an ipv4 address (ipv4-compatible, ipv4-mapped and nat64).
     * Such an address can reach the embedded ipv4 address, so it is judged by the ipv4 rules.
     */
    private const EMBEDDED_IPV4_RANGES = ['::/96', '::ffff:0:0/96', '64:ff9b::/96'];

    // Unique local addresses
    private const PRIVATE_RANGES = ['fc00::/7'];

    // Link local and (deprecated) site local addresses
    private const RESERVED_RANGES = ['fe80::/10', 'fec0::/10'];

    public static function validate(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function isPrivate(): bool
    {
        $embedded = $this->embeddedIpv4();
        if ($embedded) {
            return $embedded->isPrivate();
        }

        return $this->inAnyRange(self::PRIVATE_RANGES);
    }

    public function isReserved(): bool
    {
        $embedded = $this->embeddedIpv4();
        if ($embedded) {
            return $embedded->isReserved();
        }

        return $this->inAnyRange(self::RESERVED_RANGES);
    }

    private function embeddedIpv4(): ?Ipv4Address
    {
        if (! $this->inAnyRange(self::EMBEDDED_IPV4_RANGES)) {
            return null;
        }

        return Ipv4Address::fromString(inet_ntop(substr(inet_pton($this->ip), 12, 4)));
    }

    private function inAnyRange(array $ranges): bool
    {
        $packed = inet_pton($this->ip);
        foreach ($ranges as $range) {
            [$network, $prefixLength] = explode('/', $range);
            $prefixLength = (int) $prefixLength;
            $network = inet_pton($network);

            $bytes = intdiv($prefixLength, 8);
            $bits = $prefixLength % 8;
            if (substr($packed, 0, $bytes) !== substr($network, 0, $bytes)) {
                continue;
            }
            if ($bits === 0) {
                return true;
            }
            $mask = (0xff << (8 - $bits)) & 0xff;
            if ((ord($packed[$bytes]) & $mask) === (ord($network[$bytes]) & $mask)) {
                return true;
            }
        }

        return false;
    }
}
