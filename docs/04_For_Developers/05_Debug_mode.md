Debug mode has been removed.

## Debug logging

To enable debug logging, set the env type to dev:

```ini
[system]

; System environment: "dev" or "prod"
env = "dev"
```

Then you can use `$this->logger->debug()` in your bridge to log debug messages to stdout.

## Disable Caching

If you want to disable caching you can set cache type to array (in-memory cache):

```ini
[cache]

; Cache type: file, sqlite, memcached, array, null
type = "array"
```

Alternatively, you can comment out the cache middleware in `lib/RssBridge.php`:

```diff
diff --git a/lib/RssBridge.php b/lib/RssBridge.php
index d16f1d89..da3df8be 100644
--- a/lib/RssBridge.php
+++ b/lib/RssBridge.php
@@ -24,7 +24,7 @@ final class RssBridge

         $middlewares = [
             new BasicAuthMiddleware(),
-            new CacheMiddleware($this->container['cache']),
+            //new CacheMiddleware($this->container['cache']),
             new ExceptionMiddleware($this->container['logger']),
             new SecurityMiddleware(),
             new MaintenanceMiddleware(),
```
