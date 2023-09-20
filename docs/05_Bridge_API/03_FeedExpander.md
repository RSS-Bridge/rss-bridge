`FeedExpander` extends [`BridgeAbstract`](./02_BridgeAbstract.md) and adds functions to collect data from existing feeds.

**Usage example**: _You have discovered a site that provides feeds which are hidden and inaccessible by normal means. You want your bridge to directly read the feeds and provide them via **RSS-Bridge**_

To create a new Bridge extending `FeedExpander` you must implement all required functions of [`BridgeAbstract`](./02_BridgeAbstract.md). `FeedExpander` additionally provides following functions:

* [`parseItem`](#the-parseitem-function)
* [`getName`](#the-getname-function)
* [`getURI`](#the-geturi-function)
* [`getDescription`](#the-getdescription-function)

Find a [template](#template) at the end of this file.

**Notice:** For a standard feed only `collectData` need to be implemented. `collectData` should call `$this->collectExpandableDatas('your URI here');` to automatically load feed items and header data (will subsequently call `parseItem` for each item in the feed). You can limit the number of items to fetch by specifying an additional parameter for: `$this->collectExpandableDatas('your URI here', 10)` (limited to 10 items).

## The `parseItem` function

This function receives one item from the current feed and should return one **RSS-Bridge** item.
The default function does all the work to get the item data from the feed, whether it is RSS 1.0,
RSS 2.0 or Atom 1.0. If you have to redefine this function in your **RSS-Bridge** for whatever reason,
you should first call the parent function to initialize the item, then apply the changes that you require.

**Notice:** The following code sample is just an example. Implementation depends on your requirements!

```PHP
protected function parseItem($feedItem){
    $item = parent::parseItem($feedItem);
    $item['content'] = str_replace('rssbridge','RSS-Bridge',$feedItem->content);

    return $item;
}
```

### Helper functions

The `FeedExpander` already provides a set of functions to parse RSS or Atom items based on the specifications. Where possible make use of these functions:

Function | Description
---------|------------
`parseATOMItem` | Parses an Atom 1.0 feed item
`parseRSS_0_9_1_Item` | Parses an RSS 0.91 feed item
`parseRSS_1_0_Item` | Parses an RSS 1.0 feed item
`parseRSS_2_0_Item` | Parses an RSS 2.0 feed item

In the following list you'll find the feed tags assigned to the the **RSS-Bridge** item keys:

Function | uri | title | timestamp | author | content
---------|-----|-------|-----------|--------|--------
`parseATOMItem` | id | title | updated | author | content
`parseRSS_0_9_1_Item` | link | title | | | description
`parseRSS_1_0_Item` | link | title | dc:date | dc:creator | description
`parseRSS_2_0_Item` | link, guid | title | pubDate, dc:date | author, dc:creator | description

## The `getName` function

Returns the name of the current feed.

```PHP
return $this->name;
```

**Notice:** Only implement this function if you require different behavior!

## The `getURI` function

Return the uri for the current feed.

```PHP
return $this->uri;
```

**Notice:** Only implement this function if you require different behavior!

## The `getDescription` function

Returns the description for the current bridge.

```PHP
return $this->description;
```

**Notice:** Only implement this function if you require different behavior!

# Template

This is the template for a new bridge:

```PHP
<?php
class MySiteBridge extends FeedExpander {

	const MAINTAINER = 'No maintainer';
	const NAME = 'Unnamed bridge';
	const URI = '';
	const DESCRIPTION = 'No description provided';
	const PARAMETERS = [];
	const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $this->collectExpandableDatas('your feed URI');
    }
}
// Imaginary empty line!
```