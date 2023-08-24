<h1 align="center">Warning!</h1>

Enabling debug mode on a public server may result in malicious clients retrieving sensitive data about your server and possibly gaining access to it. Do not enable debug mode on a public server, unless you understand the implications of your doing!

***

Debug mode enables error reporting and prevents loading data from the cache (data is still written to the cache).
To enable debug mode, set in `config.ini.php`:

    enable_debug_mode = true

Allow only explicit ip addresses:

    debug_mode_whitelist[] = 127.0.0.1
    debug_mode_whitelist[] = 192.168.1.10

_Notice_:

* An empty file enables debug mode for anyone!
* The bridge whitelist still applies! (debug mode does **not** enable all bridges)

RSS-Bridge will give you a visual feedback when debug mode is enabled.

While debug mode is active, RSS-Bridge will write additional data to your servers `error.log`.

Debug mode is controlled by the static class `Debug`. It provides three core functions:

* `Debug::isEnabled()`: Returns `true` if debug mode is enabled.
* `Debug::log($message)`: Adds a message to `error.log`. It takes one parameter, which can be anything.

Example: `Debug::log('Hello World!');`

**Notice**: `Debug::log($message)` calls `Debug::isEnabled()` internally. You don't have to do that manually.