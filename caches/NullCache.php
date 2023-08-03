<?php

declare(strict_types=1);

class NullCache implements CacheInterface
{
    public function get(string $key, $default = null)
    {
    }

    public function set(string $key, $value, int $ttl = null): void
    {
    }

    public function purgeCache(int $timeout = 86400): void
    {
    }

    public function clear(): void
    {
        // TODO: Implement clear() method.
    }
}
