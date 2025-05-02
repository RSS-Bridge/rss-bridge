## General recommendations

## Test a site before building a bridge

Some sites make use of anti-bot mechanisms (e.g.: by using JavaScript) in which case they work fine in regular browsers,
but not in the PHP environment. RSS-Bridge Docker container by default resorts to using libcurl-impersonate, which helps mitigating anti-bot mechanisms.

To check if a site works with RSS-Bridge, create a new bridge using the 
[template](../05_Bridge_API/02_BridgeAbstract.md#template)
and load a valid URL (not the base URL!).

**Example (using github.com)**

```PHP
<?php
class TestBridge extends BridgeAbstract
{
    const NAME = 'Unnamed bridge';
    const URI = '';
    const DESCRIPTION = 'No description provided';
    const MAINTAINER = 'No maintainer';
    const PARAMETERS = [];
    const CACHE_TIMEOUT = 3600;

    public function collectData()
    {
        $html = getSimpleHTMLDOM('https://github.com/rss-bridge/rss-bridge');
    }
}
```

This bridge should return an empty page (HTML format)
