# getInput
The `getInput` function is used to receive a value for a parameter, specified in `const PARAMETERS`

```PHP
$this->getInput('your input name here');
```

`getInput` will either return the value for your parameter
or `null` if the parameter is unknown or not specified.

[Defined in lib/BridgeAbstract.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/BridgeAbstract.php)

# getKey
The `getKey` function is used to receive the key name to a selected list
value given the name of the list, specified in `const PARAMETERS`
Is able to work with multidimensional list arrays.

```PHP
// Given a multidimensional array like this
const PARAMETERS = [[
        'country' => [
            'name' => 'Country',
            'type' => 'list',
            'values' => [
                'North America' => [
                    'Mexico' => 'mx',
                    'United States' => 'us'
                ],
                'South America' => [
                    'Uruguay' => 'uy',
                    'Venezuela' => 've'
                ],
            ]
        ]
]],
// Provide the list name to the function
$this->getKey('country');
// if the selected value was "ve", this function will return "Venezuela"
```

`getKey` will either return the key name for your parameter or `null` if the parameter
is unknown or not specified.

[Defined in lib/BridgeAbstract.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/BridgeAbstract.php)

# getContents
The `getContents` function uses [cURL](https://secure.php.net/manual/en/book.curl.php) to acquire data from the specified URI while respecting the various settings defined at a global level by RSS-Bridge (i.e., proxy host, user agent, etc.). This function accepts a few parameters:

| Parameter | Type   | Optional   | Description
| --------- | ------ | ---------- | ----------
| `url`     | string | *required* | The URL of the contents to acquire
| `header`  | array  | *optional* | An array of HTTP header fields to set, in the format `array('Content-type: text/plain', 'Content-length: 100')`, see [CURLOPT_HTTPHEADER](https://secure.php.net/manual/en/function.curl-setopt.php)
| `opts`    | array  | *optional* | An array of cURL options in the format `array(CURLOPT_POST => 1);`, see [curl_setopt](https://secure.php.net/manual/en/function.curl-setopt.php) for a complete list of options.
| `returnFull`    | boolean  | *optional* | Specifies whether to return the response body from cURL (default) or the response body, code, headers, etc.

```PHP
$header = array('Content-type:text/plain', 'Content-length: 100');
$opts = array(CURLOPT_POST => 1);
$html = getContents($url, $header, $opts);
```

[Defined in lib/contents.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/contents.php)

# getSimpleHTMLDOM
The `getSimpleHTMLDOM` function is a wrapper for the 
[simple_html_dom](https://simplehtmldom.sourceforge.io/) [file_get_html](https://simplehtmldom.sourceforge.io/docs/1.9/api/file_get_html/) function in order to provide context by design.

```PHP
$html = getSimpleHTMLDOM('your URI');
```

[Defined in lib/contents.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/contents.php)

# getSimpleHTMLDOMCached
The `getSimpleHTMLDOMCached` function does the same as the 
[`getSimpleHTMLDOM`](#getsimplehtmldom) function,
except that the content received for the given URI is stored in a cache
and loaded from cache on the next request if the specified cache duration
was not reached.

Use this function for data that is very unlikely to change between consecutive requests to **RSS-Bridge**.
This function allows to specify the cache duration with the second parameter.

```PHP
$html = getSimpleHTMLDOMCached('your URI', 86400); // Duration 24h
```

[Defined in lib/contents.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/contents.php)

# returnClientError
The `returnClientError` function aborts execution of the current bridge
and returns the given error message with error code **400**:

```PHP
returnClientError('Your error message')
```

Use this function when the user provided invalid parameter or a required parameter is missing.

[Defined in lib/utils.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/utils.php)

# returnServerError
The `returnServerError` function aborts execution of the current bridge and returns the given error message with error code **500**:

```PHP
returnServerError('Your error message')
```

Use this function when a problem occurs that has nothing to do with the parameters provided by the user.
(like: Host service gone missing, empty data received, etc...)

[Defined in lib/utils.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/utils.php)

# defaultLinkTo
Automatically replaces any relative URL in a given string or DOM object
(i.e. the one returned by [getSimpleHTMLDOM](#getsimplehtmldom)) with an absolute URL.

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

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# backgroundToImg
Replaces tags with styles of `backgroud-image` by `<img />` tags.

```php
backgroundToImg(mixed $htmlContent) : object
```

Returns a DOM object (even if provided a string).

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# extractFromDelimiters
Extract the first part of a string matching the specified start and end delimiters.
```php
function extractFromDelimiters(string $string, string $start, string $end) : mixed
```

Returns the extracted string if delimiters were found and false otherwise.

**Example**

```php
$string = '<div>Post author: John Doe</div>';
$start = 'author: ';
$end = '<';
$extracted = extractFromDelimiters($string, $start, $end);

// Output
// 'John Doe'
```

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# stripWithDelimiters
Remove one or more part(s) of a string using a start and end delimiter.
It is the inverse of `extractFromDelimiters`.

```php
function stripWithDelimiters(string $string, string $start, string $end) : string
```

Returns the cleaned string, even if no delimiters were found.

**Example**

```php
$string = 'foo<script>superscript()</script>bar';
$start = '<script>';
$end = '</script>';
$cleaned = stripWithDelimiters($string, $start, $end);

// Output
// 'foobar'
```

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# stripRecursiveHTMLSection
Remove HTML sections containing one or more sections using the same HTML tag.

```php
function stripRecursiveHTMLSection(string $string, string $tag_name, string $tag_start) : string
```

**Example**

```php
$string = 'foo<div class="ads"><div>ads</div>ads</div>bar';
$tag_name = 'div';
$tag_start = '<div class="ads">';
$cleaned = stripRecursiveHTMLSection($string, $tag_name, $tag_start);

// Output
// 'foobar'
```

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# markdownToHtml
Converts markdown input to HTML using [Parsedown](https://parsedown.org/).

| Parameter | Type   | Optional   | Description
| --------- | ------ | ---------- | ----------
| `string`  | string | *required* | The URL of the contents to acquire
| `config`  | array  | *optional* | An array of Parsedown options in the format `['breaksEnabled' => true]`

Valid options:
| Option          | Default | Description
| --------------- | ------- | -----------
| `breaksEnabled` | `false` | Enable automatic line breaks
| `markupEscaped` | `false` | Escape inline markup (HTML)
| `urlsLinked`    | `true`  | Automatically convert URLs to links

```php
function markdownToHtml(string $string, array $config = []) : string
```

**Example**
```php
$input = <<<EOD
RELEASE-2.8
 * Share QR code of a token
 * Dark mode improvemnet
 * Fix some layout issues
 * Add shortcut to launch the app with screenshot mode on
 * Translation improvements
EOD;
$html = markdownToHtml($input);

// Output:
// <p>RELEASE-2.8</p>
// <ul>
// <li>Share QR code of a token</li>
// <li>Dark mode improvemnet</li>
// <li>Fix some layout issues</li>
// <li>Add shortcut to launch the app with screenshot mode on</li>
// <li>Translation improvements</li>
// </ul>
```

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# e
The `e` function is used to convert special characters to HTML entities

```PHP
e('0 < 1 and 2 > 1');
```

`e` will return the content of the string escape that can be rendered as is in HTML

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# truncate
The `truncate` function is used to shorten a string if exceeds a certain length, and add a string indicating that the string has been shortened.

```PHP
truncate('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed a neque nunc. Nam nibh sem.', 20 , '...');
```

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# sanitize
The `sanitize` function is used to remove some tags from a given HTML text.

```PHP
$html = '<head><title>Sample Page</title></head>
<body><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit...</p>
<iframe src="https://www.example.com" width="600" height="400" frameborder="0" allowfullscreen></iframe>
</body>
</html>';
$tags_to_remove = ['script', 'iframe', 'input', 'form'];
$attributes_to_keep = ['title', 'href', 'src'];
$text_to_keep = [];
sanitize($html, $tags_to_remove, $attributes_to_keep, $text_to_keep);
```

This function returns a simplehtmldom object of the remaining contents.

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# convertLazyLoading
The `convertLazyLoading` function is used to convert onvert lazy-loading images and frames (video embeds) into static elements. It accepts the HTML content as HTML objects or string objects. It returns the HTML content with fixed image/frame URLs (same type as input).

```PHP
$html = '<html>
<body style="background-image: url('bgimage.jpg');">
<h1>Hello world!</h1>
</body>
</html>
backgroundToImg($html);
```

[Defined in lib/html.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/html.php)

# Json::encode
The `Json::encode` function is used to encode a value as à JSON string.

```PHP
$array = [
    "foo" => "bar",
    "bar" => "foo",
];
Json::encode($array, true, true);
```

[Defined in lib/utils.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/utils.php)

# Json::decode
The `Json::decode` function is used to decode a JSON string into à PHP variable.

```PHP
$json = '{
    "foo": "bar",
    "bar": "foo"
}';
Json::decode($json);
```

[Defined in lib/utils.php](https://github.com/RSS-Bridge/rss-bridge/blob/master/lib/utils.php)
