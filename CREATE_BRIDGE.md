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


