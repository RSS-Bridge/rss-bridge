# Howto create a bridge

A bridge is an class that allows rss-bridge to create a RSS feed from a website.
The bridge is a PHP file, located in the [`bridges/`](bridges/) folder.

Read the following chapters an make sure to read the [Guidelines](#guidelines)!

## Specifications

A rss bridge must extend the `BridgeAbstract` class and implement the following functions :

* [`loadMetadatas`](#the-loadmetadatas-function)
* [`collectData`](#the-collectdata-function)
* [`getName`](#the-getname-function)
* [`getURI`](#the-geturi-function)
* [`getCacheDuration`](#the-getcacheduration-function)

Find a [template](#template) at the end of this file.

## The `loadMetadatas` function

This function is used by rss-bridge to determine the name, maintainer name, website, last updated date... of the bridge, and the user parameters.

### Basic metadatas

The basic metadatas are :

```PHP
$this->maintainer // Name of the maintainer
$this->name // Name of the bridge
$this->uri // URI to the target website of the bridge ("http://....")
$this->description // A brief description of the bridge
$this->update // Date of last change in format "yyyy-mm-dd"
$this->parameters // (optional) Definition of additional parameters
```

Find a description of `$this->parameters` [below](#parameters)

The default values are :

```PHP
$this->maintainer = 'No maintainer';
$this->name = "Unnamed bridge";
$this->uri = "";
$this->description = 'No description provided';
$this->parameters = array();
```

### Parameters

Parameters are defined in a JSON-like format, which is parsed and transformed into a HTML `<form>` by rss-bridge.

These datas goes into the `$this->parameters` array, which is not mandatory if your bridge doesn't take any parameter.

Every possible usage of a bridge is an array element.

The array can be a key-based array, but it is not necessary. The following syntaxes are hereby correct :

```PHP
$this->parameters[] = ...
$this->parameters['First usage of my bridge'] = ...
```

It is worth mentionning that you can also define a set of parameters that will be applied to every possible utilisation of your bridge.
To do this, just create a parameter array with the `global` key :

```PHP
$this->parameters['global'] = ...
```

### Format specifications

Every `$this->parameters` element is a JSON array of cluster (`[ ... ]`) containing all input fields.

Following elements are supported :

Parameter Name | Required | Type | Supported values | Description
---------------|----------|------|------------------| -----------
`name`|**yes**|Text||Input name as displayed to the user
`identifier`|**yes**|Text||Identifier, which will be the key in the `$param` array for the [`collectData`](#the-collectdata-function) function
`type`|no|Text|`text`, `number`, `list`, `checkbox`|Type of the input, default is text
`required`|no|Boolean|`true`, `false`|Set this if you want your attribute to be required
`values`|no|Text|`[ {"name" : option1Name, "value" : "option1Value"}, ... ]`| Values list, required with the '`list`' type
`title`|no|Text||Will be shown as tooltip when mouse-hovering over the input

Hence, the most basic parameter definition is the following :

```PHP
...
	$this->parameters[] =
	'[
		{
			"name" : "Username",
			"identifier" : "u"
		}
	]';
...
```

## The `collectData` function

This function takes as a parameter an array called `$param`, that is automatically filled with values from the user, according to the values defined in the parameters array in `loadMetadatas`.
This function is the place where all the website scrapping and the RSS feed generation process must be implemented.

RSS elements collected by this function must be stored in the class variable `items[]`.

Every RSS element is an instance of the `Item` class.

## Items

The `Item` class is used to store parameter that are collected in the [`collectData`](#the-collectdata-function) function. Following properties are supported by rss-bridge :

```PHP
$item->uri // URI to reach the subject ("http://...")
$item->thumbnailUri // URI for the thumbnail ("http://...")
$item->title // Title of the item
$item->name // Name of the item
$item->timestamp // Timestamp of the item in numeric format (use strtotime)
$item->author // Name of the author
$item->content // Content in HTML format
```

In order to create a new item you'll have to escape the **I** in **I**tem like this :

```PHP
$item = new \Item();
```

### Item usage

Which items are necessary depends on the output format. There are two formats that support literally any property in the `$item`:

* JSON
* Plaintext

The following list provides an overview of the parameters used by the other formats :

Parameter | ATOM | HTML | (M)RSS
----------|------|------|-------
`uri`|X|X|X
`thumbnailUri`||X
`title`|X|X|X
`name`|X||
`timestamp`|X|X|X
`author`|X|X|X
`content`|X|X|X

## The `getName` function

This function returns the name of the bridge as it will be displayed on the main page of rss-bridge or on top of the feed output (HTML, ATOM, etc...).

```PHP
	public function getName(){
		return ''; // Insert your bridge name here!
	}
```

## The `getURI` function

This function returns the URI to the destination site of the bridge. It will be used on the main page of rss-bridge when clicking your bridge name.

```PHP
	public function getURI(){
		return ''; // Insert your URI here!
	}
```

## The `getCacheDuration` function

This function returns the time in **seconds** during which rss-bridge will output cached values instead of re-generating a RSS feed.

**Notice:** rss-bridge will return `3600` seconds (1 hour) by default, so you only have to implement this function if you require different timing!

```PHP
	public function getCacheDuration(){
		return 3600; // 1 hour
	}
```

# Bridge Abstract functions

All bridges extend from `BridgeAbstract` and therefore are able to make use of functions defined in `BridgeAbstract`. 

Following functions should be used for good practice and will support you with your bridge :

* [`returnError`](#the-returnerror-function)
* [`file_get_html`](#the-file_get_html-function)

## The `returnError` function

This function aborts execution of the current bridge and returns the given error message with the provided error number :

```PHP
$this->returnError('Your error message', 404)
```

Check the [list of error codes](https://en.wikipedia.org/wiki/List_of_HTTP_status_codes) for applicable error numbers.

## The `file_get_html` function

This function is a wrapper around the simple_html_dom file_get_html function in order to provide context by design. It is considered good practice to use this function.

```PHP
$html = $this->file_get_html('your URI');
```

# Guidelines

* scripts (eg. Javascript) must be stripped out. Make good use of `strip_tags()` and `preg_replace()`
* each bridge must present data within 8 seconds (adjust iterators accordingly)
* cache timeout must be fine-tuned so that each refresh can provide 1 or 2 new elements on busy periods
* `<audio>` and `<video>` must not autoplay. Seriously.
* do everything you can to extract valid timestamps. Translate formats, use API, exploit sitemap, whatever. Free the data!
* don't create duplicates. If the website runs on WordPress, use the generic WordPress bridge if possible.
* maintain efficient and well-commented code :wink:

# Template

This is the minimum template for a new bridge:

```PHP
<?php
class MySiteBridge extends BridgeAbstract{
	public function loadMetadatas(){
		$this->maintainer = 'No maintainer';
		$this->name = $this->getName();
		$this->uri = $this->getURI();
		$this->description = 'No description provided';
		$this->parameters = array();
	}

	public function collectData(array $params){
		// Implement your bridge here!
	}

	public function getName(){
		return ''; // Insert your bridge name here!
	}

	public function getURI(){
		return ''; // Insert your URI here!
	}
}

```
