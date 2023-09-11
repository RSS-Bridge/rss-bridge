<?php

interface CacheInterface
{
    public function get(string $key, $default = null);

    public function set(string $key, $value, int $ttl = null): void;

    public function delete(string $key): void;

    public function clear(): void;

    public function prune(): void;
}
