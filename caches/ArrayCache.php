<?php

declare(strict_types=1);

class ArrayCache implements CacheInterface
{
    private array $data = [];

    public function get(string $key, $default = null)
    {
        $item = $this->data[$key] ?? null;
        if (!$item) {
            return $default;
        }
        $expiration = $item['expiration'];
        if ($expiration === 0 || $expiration > time()) {
            return $item['value'];
        }
        $this->delete($key);
        return $default;
    }

    public function set(string $key, $value, int $ttl = null): void
    {
        $this->data[$key] = [
            'key'           => $key,
            'value'         => $value,
            'expiration'    => $ttl === null ? 0 : time() + $ttl,
        ];
    }

    public function delete(string $key): void
    {
        unset($this->data[$key]);
    }

    public function clear(): void
    {
        $this->data = [];
    }

    public function prune(): void
    {
        foreach ($this->data as $key => $item) {
            $expiration = $item['expiration'];
            if ($expiration === 0 || $expiration > time()) {
                continue;
            }
            $this->delete($key);
        }
    }
}
