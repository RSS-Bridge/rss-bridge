<?php

interface CacheInterface
{
    public function set(string $key, $value = true, int $ttl = null): void;

    public function get(string $key, $default = null);

    public function delete(string $key): void;

    public function clear(): void;
}
