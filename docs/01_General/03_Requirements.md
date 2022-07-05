**RSS-Bridge** requires either of the following:

## A Web server* with:

  - PHP 7.4 (or higher)
  - [`openssl`](https://secure.php.net/manual/en/book.openssl.php) extension
  - [`libxml`](https://secure.php.net/manual/en/book.libxml.php) extension (enabled by default, see [PHP Manual](http://php.net/manual/en/libxml.installation.php))
  - [`mbstring`](https://secure.php.net/manual/en/book.mbstring.php) extension
  - [`simplexml`](https://secure.php.net/manual/en/book.simplexml.php) extension
  - [`curl`](https://secure.php.net/manual/en/book.curl.php) extension
  - [`json`](https://secure.php.net/manual/en/book.json.php) extension
  - [`filter`](https://secure.php.net/manual/en/book.filter.php) extension
  - [`zip`](https://secure.php.net/manual/en/book.zip.php) (for some bridges)
  - [`sqlite3`](http://php.net/manual/en/book.sqlite3.php) extension (only when using SQLiteCache)

Enable extensions by un-commenting the corresponding line in your PHP configuration (`php.ini`).


## A Linux server with:

 - Docker server configured (Any recent version should do)
 - 100MB of disk space

To setup RSS Bridge using Docker, see the [Docker Guide](../03_For_Hosts/03_Docker_Installation.md) on installing RSS Bridge.