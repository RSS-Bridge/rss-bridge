# Howto create a bridge

A bridge is an interface that allows rss-bridge to create a RSS feed from a website.
The bridge is a PHP file, located in the `bridges/` folder.

##Specifications

A rss bridge must extend the `BridgeAbstract` class, and implement the following functions :

* The `loadMetadatas` function, described below,
* The `getCacheDuration` function, describing the time during which rss-bridge will output cached values instead of re-generating a RSS feed.
* The `collectData` function, also described below.

##The `collectData` function

This function takes as a parameter an array called `$param`, that is automatically filled with values from the user, according to the values setted in `loadMetadatas`.
This function is the place where all the website scrapping and the RSS feed generation process will go.

The RSS elements are stored in the class variable `items[]`.

Every RSS element is an instance of the `Item` class.

##The `loadMetadatas` function

This function is the one used by rss-bridge core to determine the name, maintainer name, website, last updated date... of the bridge, and the user parameters.

### Basic metadatas.

The basic metadatas are the following :

```PHP
$this->maintainer
$this->name
$this->uri
$this->description
$this->update
```

The default values are the following :

```PHP
$this->name = "Unnamed bridge";
$this->uri = "";
$this->description = 'No description provided';
$this->maintainer = 'No maintainer';
```

### Parameters

Parameters use a JSON-like format, which is parsed and transformed to HTML `<form>` by rss-bridge.

These datas goes into the `$this->parameters` array, which is not mandatory if your bridge doesn't take any parameter.

Every possible usage of a bridge is an array element.

The array can be a key-based array, but it is not necessary. The following syntaxes are hereby correct :

```PHP
$this->parameters[] = ...
$this->parameters['First usage of my bridge'] = ...
```
It is worth mentionning that you can also define a set of parameters that will be applied to every possible utilisation of your bridge.
To do this, just create a parameter array with the `global` key.

### Format specifications

Every `$this->parameters` element is a JSON array (`[ ... ]`) containing every input.

It needs the following elements to be setted :
* name, the input name as displayed to the user
* identifier, which will be the key in the `$param` array for the corresponding data.

Hence, the most basic parameter definition is the following:

```PHP
	$this->parameters =
	'[
		{
			"name" : "Username",
			"identifier" : "u"

		}
	]';
```

####Optional parameters

Here is a list of optional parameters for the input :

Parameter Name | Parameter values | Description
---------------|------------------|------------
type|text, number, list, checkbox| Type of the input, default is text
required| true | Set this if you want your attribute to be required
values| [ {"name" : option1Name, "value" : "option1Value"}, ...] | Values list, required with the 'list' type
title| text | Will be shown as tooltip when mouse-hovering over the input

#### Guidelines

  * scripts (eg. Javascript) must be stripped out. Make good use of `strip_tags()` and `preg_replace()`
  * bridge must present data within 8 seconds (adjust iterators accordingly)
  * cache timeout must be fine-tuned so that each refresh can provide 1 or 2 new elements on busy periods
  * `<audio>` and `<video>` must not autoplay. Seriously.
  * do everything you can to extract valid timestamps. Translate formats, use API, exploit sitemap, whatever. Free the data!
  * don't create duplicates. If the website runs on WordPress, use the generic WordPress bridge if possible.
  * maintain efficient and well-commented code :wink:
