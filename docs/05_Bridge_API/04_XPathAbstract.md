`XPathAbstract` extends [`BridgeAbstract`](./02_BridgeAbstract.md) and adds functionality for generating feeds based on _XPath expressions_. It makes creation of new bridges easy and if you're familiar with XPath expressions this class is probably the right point for you to start with.

At the end of this document you'll find a complete [template](#template) based on these instructions.

***
# Required constants
To create a new Bridge based on `XPathAbstract` your inheriting class should specify a set of constants describing the feed and the XPath expressions.

It is advised to override constants inherited from [`BridgeAbstract`](./02_BridgeAbstract.md#step-3---add-general-constants-to-the-class) aswell.

## Class constant `FEED_SOURCE_URL`
Source Web page URL (should provide either HTML or XML content). You can specify any website URL which serves data suited for display in RSS feeds

## Class constant `XPATH_EXPRESSION_FEED_TITLE`
XPath expression for extracting the feed title from the source page. If this is left blank or does not provide any data `BridgeAbstract::getName()` is used instead as the feed's title.

## Class constant `XPATH_EXPRESSION_FEED_ICON`
XPath expression for extracting the feed favicon URL from the source page. If this is left blank or does not provide any data `BridgeAbstract::getIcon()` is used instead as the feed's favicon URL.

## Class constant `XPATH_EXPRESSION_ITEM`
XPath expression for extracting the feed items from the source page. Enter an XPath expression matching a list of dom nodes, each node containing one feed article item in total (usually a surrounding `<div>` or `<span>` tag). This will be the context nodes for all of the following expressions. This expression usually starts with a single forward slash.

## Class constant `XPATH_EXPRESSION_ITEM_TITLE`
XPath expression for extracting an item title from the item context. This expression should match a node contained within each article item node containing the article headline. It should start with a dot followed by two forward slashes, referring to any descendant nodes of the article item node.

## Class constant `XPATH_EXPRESSION_ITEM_CONTENT`
XPath expression for extracting an item's content from the item context. This expression should match a node contained within each article item node containing the article content or description. It should start with a dot followed by two forward slashes, referring to any descendant nodes of the article item node.

## Class constant `XPATH_EXPRESSION_ITEM_URI`
XPath expression for extracting an item link from the item context. This expression should match a node's attribute containing the article URL (usually the href attribute of an `<a>` tag). It should start with a dot followed by two forward slashes, referring to any descendant nodes of the article item node. Attributes can be selected by prepending an `@` char before the attributes name.

## Class constant `XPATH_EXPRESSION_ITEM_AUTHOR`
XPath expression for extracting an item author from the item context. This expression should match a node contained within each article item node containing the article author's name. It should start with a dot followed by two forward slashes, referring to any descendant nodes of the article item node.

## Class constant `XPATH_EXPRESSION_ITEM_TIMESTAMP`
XPath expression for extracting an item timestamp from the item context. This expression should match a node or node's attribute containing the article timestamp or date (parsable by PHP's strtotime function). It should start with a dot followed by two forward slashes, referring to any descendant nodes of the article item node. Attributes can be selected by prepending an `@` char before the attributes name.

## Class constant `XPATH_EXPRESSION_ITEM_ENCLOSURES`
XPath expression for extracting item enclosures (media content like images or movies) from the item context. This expression should match a node's attribute containing an article image URL (usually the src attribute of an <img> tag or a style attribute). It should start with a dot followed by two forward slashes, referring to any descendant nodes of the article item node. Attributes can be selected by prepending an `@` char before the attributes name.

## Class constant `XPATH_EXPRESSION_ITEM_CATEGORIES`
XPath expression for extracting an item category from the item context. This expression should match a node or node's attribute contained within each article item node containing the article category. This could be inside <div> or <span> tags or sometimes be hidden in a data attribute. It should start with a dot followed by two forward slashes, referring to any descendant nodes of the article item node. Attributes can be selected by prepending an `@` char before the attributes name.

## Class constant `SETTING_FIX_ENCODING`
Turns on automatic fixing of encoding errors. Set this to true for fixing feed encoding by invoking PHP's `utf8_decode` function on all extracted texts. Try this in case you see "broken" or "weird" characters in your feed where you'd normally expect umlauts or any other non-ascii characters.

# Optional methods
`XPathAbstract` offers a set of methods which can be overridden by derived classes for fine tuning and customization. This is optional. The methods provided for overriding can be grouped into three categories.

## Methods for providing XPath expressions
Usually XPath expressions are defined in the class constants described above. By default the following base methods just return the value of its corresponding class constant. However deriving classed can override them in case if XPath expressions need to be formed dynamically or based on conditions. In case any of these methods is defined, the method's return value is used instead of the corresponding constant for providing the value.

### Method `getSourceUrl()`
Should return the source Web page URL used as a base for applying the XPath expressions.

### Method `getExpressionTitle()`
Should return the XPath expression for extracting the feed title from the source page.

### Method `getExpressionIcon()`
Should return the XPath expression for extracting the feed favicon from the source page.

### Method `getExpressionItem()`
Should return the XPath expression for extracting the feed items from the source page.

### Method `getExpressionItemTitle()`
Should return the XPath expression for extracting an item title from the item context.

### Method `getExpressionItemContent()`
Should return the XPath expression for extracting an item's content from the item context.

### Method `getExpressionItemUri()`
Should return the XPath expression for extracting an item link from the item context.

### Method `getExpressionItemAuthor()`
Should return the XPath expression for extracting an item author from the item context.

### Method `getExpressionItemTimestamp()`
Should return the XPath expression for extracting an item timestamp from the item context.

### Method `getExpressionItemEnclosures()`
Should return the XPath expression for extracting item enclosures (media content like images or movies) from the item context.

### Method `getExpressionItemCategories()`
Should return the XPath expression for extracting an item category from the item context.

### Method `getSettingFixEncoding()`
Should return the Fix encoding setting value (bool true or false).

## Methods for providing feed data
Those methods are invoked for providing the HTML source as a base for applying the XPath expressions as well as feed meta data as the title and icon.

### Method `provideWebsiteContent()`
This method should return the HTML source as a base for the XPath expressions. Usually it merely returns the HTML content of the URL specified in the constant `FEED_SOURCE_URL` retrieved by curl. Some sites however require user authentication mechanisms, the use of special cookies and/or headers, where the direct retrival using standard curl would not suffice. In that case this method should be overridden and take care of the page retrival.

### Method `provideFeedTitle()`
This method should provide the feed title. Usually the XPath expression defined in `XPATH_EXPRESSION_FEED_TITLE` is used for extracting the title directly from the page source.

### Method `provideFeedIcon()`
This method should provide the feed title. Usually the XPath expression defined in `XPATH_EXPRESSION_FEED_ICON` is used for extracting the title directly from the page source.

### Method `provideFeedItems()`
This method should provide the feed items. Usually the XPath expression defined in `XPATH_EXPRESSION_ITEM` is used for extracting the items from the page source. All other XPath expressions are applied on a per-item basis, item by item, and only on the item's contents.

## Methods for formatting and filtering feed item attributes
The following methods are invoked after extraction of the feed items from the source. Each of them expect one parameter, the value of the corresponding field, which then can be processed and transformed by the method. You can override these methods in order to format or filter parts of the feed output.

### Method `formatItemTitle()`
Accepts the items title values as parameter, processes and returns it. Should return a string.

### Method `formatItemContent()`
Accepts the items content as parameter, processes and returns it. Should return a string.

### Method `formatItemUri()`
Accepts the items link URL as parameter, processes and returns it. Should return a string.

### Method `formatItemAuthor()`
Accepts the items author as parameter, processes and returns it. Should return a string.

### Method `formatItemTimestamp()`
Accepts the items creation timestamp as parameter, processes and returns it. Should return a unix timestamp as integer.

### Method `cleanImageUrl()`
Method invoked for cleaning feed icon and item image URL's. Extracts the image URL from the passed parameter, stripping any additional content. Furthermore makes sure that relative image URL's get transformed to absolute ones.

### Method `fixEncoding()`
Only invoked when class constant `SETTING_FIX_ENCODING` is set to true. It then passes all extracted string values through PHP's `utf8_decode` function.

### Method `generateItemId()`
This method plays in important role for generating feed item ids for all extracted items. Every feed item needs an unique identifier (Uid), so that your feed reader updates the original item instead of adding a duplicate in case an items content is updated on the source site. Usually the items link URL is a good candidate the the Uid.

***

# Template

Use this template to create your own bridge. Please remove any unnecessary comments and parameters.

```PHP
<?php

class TestBridge extends XPathAbstract {
    const NAME = 'Test';
    const URI = 'https://www.unbemerkt.eu/de/blog/';
    const DESCRIPTION = 'Test';
    const MAINTAINER = 'your name';
    const CACHE_TIMEOUT = 3600;

    const FEED_SOURCE_URL = 'https://www.unbemerkt.eu/de/blog/';
    const XPATH_EXPRESSION_ITEM = '/html[1]/body[1]/section[1]/section[1]/div[1]/div[1]/div[1]/div[1]/div[1]/div[*]/article[1]';
    const XPATH_EXPRESSION_ITEM_TITLE = './/a[@target="_self"]';
    const XPATH_EXPRESSION_ITEM_CONTENT = './/div[@class="post-content"]';
    const XPATH_EXPRESSION_ITEM_URI = './/a[@class="more-btn"]/@href';
    const XPATH_EXPRESSION_ITEM_AUTHOR = '/html[1]/body[1]/section[1]/div[2]/div[1]/div[1]/h1[1]';
    const XPATH_EXPRESSION_ITEM_TIMESTAMP = './/time/@datetime';
    const XPATH_EXPRESSION_ITEM_ENCLOSURES = './/img/@data-src';
    const SETTING_FIX_ENCODING = false;
}
```