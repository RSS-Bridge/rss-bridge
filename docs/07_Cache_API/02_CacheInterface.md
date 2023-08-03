See `CacheInterface`.

```php
interface CacheInterface
{
    public function set($key, $value, int $ttl = null): void;

    public function get($key, $default = null);

    public function clear(): void;

    public function purgeCache(int $timeout = 86400): void;
}
```
