A _Bridge_ is a class that allows **RSS-Bridge** to create an RSS-feed from a website.
A _Bridge_ represents one element on the [Welcome screen](../01_General/04_Screenshots.md)
and covers one or more sites to return feeds for.
It is developed in a PHP file located in the `bridges/` folder (see [Folder structure](../04_For_Developers/03_Folder_structure.md))
and extends one of the base classes of **RSS-Bridge**:

Base class | Description
-----------|------------
[`BridgeAbstract`](./02_BridgeAbstract.md) | This class is intended for standard _Bridges_ that need to filter HTML pages for content.
[`FeedExpander`](./03_FeedExpander.md) | Expand/modify existing feed urls
[`WebDriverAbstract`](./04_WebDriverAbstract) |
[`XPathAbstract`](./05_XPathAbstract) | This class is meant as an alternative base class for bridge implementations. It offers preliminary functionality for generating feeds based on _XPath expressions_.

For more information about how to create a new _Bridge_, read [How to create a new Bridge?](./01_How_to_create_a_new_bridge.md)