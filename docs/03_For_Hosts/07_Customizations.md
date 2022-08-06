RSS-Bridge ships a few options the host may or may not activate. All options are listed in the [config.default.ini.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/config.default.ini.php) file, see [Custom Configuration](08_Custom_Configuration.md) section for more information.

## Customizable cache timeout

Sometimes it is necessary to specify custom timeouts to update contents more frequently than the bridge maintainer intended. In these cases the client may specify a custom cache timeout to prevent loading contents from cache earlier (or later).

This option can be activated by setting the [`cache.custom_timeout`](08_Custom_Configuration.md#custom_timeout) option to `true`. When enabled each bridge receives an additional parameter `Cache timeout in seconds` that can be set to any value between 1 and 86400 (24 hours). If the value is not within the limits the default settings apply (as specified by the bridge maintainer).

The cache timeout is send to RSS-Bridge using the `_cache_timeout` parameter. RSS-Bridge will return an error message if the parameter is received and the option is disabled.
