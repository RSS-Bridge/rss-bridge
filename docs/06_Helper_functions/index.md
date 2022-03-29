# getInput
The `getInput` function is used to receive a value for a parameter, specified in `const PARAMETERS`

```PHP
$this->getInput('your input name here');
```

`getInput` will either return the value for your parameter or `null` if the parameter is unknown or not specified.

# getContents
The `getContents` function uses [cURL](https://secure.php.net/manual/en/book.curl.php) to acquire data from the specified URI while respecting the various settings defined at a global level by RSS-Bridge (i.e., proxy host, user agent, etc.). This function accepts a few parameters:

| Parameter | Type   | Optional   | Description
| --------- | ------ | ---------- | ----------
| `url`     | string | *required* | The URL of the contents to acquire
| `header`  | array  | *optional* | An array of HTTP header fields to set, in the format `array('Content-type: text/plain', 'Content-length: 100')`, see [CURLOPT_HTTPHEADER](https://secure.php.net/manual/en/function.curl-setopt.php)
| `opts`    | array  | *optional* | An array of cURL options in the format `array(CURLOPT_POST => 1);`, see [curl_setopt](https://secure.php.net/manual/en/function.curl-setopt.php) for a complete list of options.

```PHP
$header = array('Content-type:text/plain', 'Content-length: 100');
$opts = array(CURLOPT_POST => 1);
$html = getContents($url, $header, $opts);
```

# getSimpleHTMLDOM
The `getSimpleHTMLDOM` function is a wrapper for the [simple_html_dom](http://simplehtmldom.sourceforge.net/) [file_get_html](http://simplehtmldom.sourceforge.net/manual_api.htm#api) function in order to provide context by design.

```PHP
$html = getSimpleHTMLDOM('your URI');
```
# getSimpleHTMLDOMCached
The `getSimpleHTMLDOMCached` function does the same as the [`getSimpleHTMLDOM`](#getsimplehtmldom) function, except that the content received for the given URI is stored in a cache and loaded from cache on the next request if the specified cache duration was not reached. Use this function for data that is very unlikely to change between consecutive requests to **RSS-Bridge**. This function allows to specify the cache duration with the second parameter (default is 24 hours / 86400 seconds).

```PHP
$html = getSimpleHTMLDOMCached('your URI', 86400); // Duration 24h
```

**Notice:** Due to the current implementation a value greater than 86400 seconds (24 hours) will not work as the cache is purged every 24 hours automatically.

# returnError
**Notice:** Whenever possible make use of [`returnClientError`](#returnclienterror) or [`returnServerError`](#returnservererror)

The `returnError` function aborts execution of the current bridge and returns the given error message with the provided error number:

```PHP
returnError('Your error message', 404);
```

Check the [list of error codes](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes) for applicable error numbers.

# returnClientError
The `returnClientError` function aborts execution of the current bridge and returns the given error message with error code **400**:

```PHP
returnClientError('Your error message')
```

Use this function when the user provided invalid parameter or a required parameter is missing.

# returnServerError
The `returnServerError` function aborts execution of the current bridge and returns the given error message with error code **500**:

```PHP
returnServerError('Your error message')
```

Use this function when a problem occurs that has nothing to do with the parameters provided by the user. (like: Host service gone missing, empty data received, etc...)

# defaultLinkTo
Automatically replaces any relative URL in a given string or DOM object (i.e. the one returned by [getSimpleHTMLDOM](#getsimplehtmldom)) with an absolute URL.

```php
defaultLinkTo ( mixed $content, string $server ) : object
```

Returns a DOM object (even if provided a string).

**Remarks**

* Only handles `<a>` and `<img>` tags.

**Example**

```php
$html = '<img src="/blob/master/README.md">';

$html = defaultLinkTo($html, 'https://www.github.com/rss-bridge/rss-bridge'); // Using custom server
$html = defaultLinkTo($html, $this->getURI()); // Using bridge URL

// Output
// <img src="https://www.github.com/rss-bridge/rss-bridge/blob/master/README.md">
```