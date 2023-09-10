<?php

declare(strict_types=1);

class NullCache implements CacheInterface
{
    public function get(string $key, $default = null)
    {
        return $default;
    }

    public function set(string $key, $value, int $ttl = null): void
    {
    }

    public function delete(string $key): void
    {
    }

    public function clear(): void
    {
    }

    public function prune(): void
    {
    }
}
