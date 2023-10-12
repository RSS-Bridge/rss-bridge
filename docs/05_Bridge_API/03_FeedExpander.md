**Usage example**: _You have discovered a site that provides feeds which are hidden and inaccessible by normal means. You want your bridge to directly read the feeds and provide them via **RSS-Bridge**_

Find a [template](#template) at the end of this file.

**Notice:** For a standard feed only `collectData` need to be implemented. `collectData` should call `$this->collectExpandableDatas('your URI here');` to automatically load feed items and header data (will subsequently call `parseItem` for each item in the feed). You can limit the number of items to fetch by specifying an additional parameter for: `$this->collectExpandableDatas('your URI here', 10)` (limited to 10 items).

## The `parseItem` method

This method receives one item from the current feed and should return one **RSS-Bridge** item.
The default function does all the work to get the item data from the feed, whether it is RSS 1.0,
RSS 2.0 or Atom 1.0.

**Notice:** The following code sample is just an example. Implementation depends on your requirements!

```PHP
protected function parseItem(array $item)
{
    $item['content'] = str_replace('rssbridge','RSS-Bridge',$item['content']);
    return $item;
}
```

### Feed parsing

How rss-bridge processes xml feeds:

Function | uri | title | timestamp | author | content
---------|-----|-------|-----------|--------|--------
`atom` | id | title | updated | author | content
`rss 0.91` | link | title | | | description
`rss 1.0` | link | title | dc:date | dc:creator | description
`rss 2.0` | link, guid | title | pubDate, dc:date | author, dc:creator | description

# Template

This is the template for a new bridge:

```PHP
<?php
class MySiteBridge extends FeedExpander
{

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
```