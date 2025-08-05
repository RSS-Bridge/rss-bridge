# How to create a completely new bridge

New code files MUST have `declare(strict_types=1);` at the top of file:

```php
<?php

declare(strict_types=1);
```

Create the new bridge in e.g. `bridges/BearBlogBridge.php`:

```php
<?php

declare(strict_types=1);

class BearBlogBridge extends BridgeAbstract
{
    const NAME = 'BearBlog (bearblog.dev)';

    public function collectData()
    {
        $dom = getSimpleHTMLDOM('https://herman.bearblog.dev/blog/');
        foreach ($dom->find('.blog-posts li') as $li) {
            $a = $li->find('a', 0);
            $this->items[] = [
                'title' => $a->plaintext,
                'uri' => 'https://herman.bearblog.dev' . $a->href,
            ];
        }
    }
}
```

Learn more in [bridge api](https://rss-bridge.github.io/rss-bridge/Bridge_API/index.html).
