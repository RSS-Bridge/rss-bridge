RSS-Bridge ships a few options the host may or may not activate.
All options are listed in the [config.default.ini.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/config.default.ini.php) file,
see [Custom Configuration](08_Custom_Configuration.md) section for more information.

## Customizable cache timeout

Sometimes it is necessary to specify custom timeouts to update contents more frequently
than the bridge maintainer intended.
In these cases the client may specify a custom cache timeout to prevent loading contents
from cache earlier (or later).

This option can be activated by setting the [`cache.custom_timeout`](08_Custom_Configuration.md#custom_timeout) option to `true`.
When enabled each bridge receives an additional parameter `Cache timeout in seconds`
that can be set to any value.
