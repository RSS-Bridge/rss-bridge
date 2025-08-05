Debug mode has been removed.

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