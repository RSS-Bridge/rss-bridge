# RSS-Bridge

![RSS-Bridge](static/logo_600px.png)

RSS-Bridge is a PHP project capable of generating RSS and Atom feeds for websites that don't have one.

[![LICENSE](https://img.shields.io/badge/license-UNLICENSE-blue.svg)](UNLICENSE)
[![GitHub release](https://img.shields.io/github/release/rss-bridge/rss-bridge.svg?logo=github)](https://github.com/rss-bridge/rss-bridge/releases/latest)
[![irc.libera.chat](https://img.shields.io/badge/irc.libera.chat-%23rssbridge-blue.svg)](https://web.libera.chat/#rssbridge)
[![Chat on Matrix](https://matrix.to/img/matrix-badge.svg)](https://matrix.to/#/#rssbridge:libera.chat)
[![Actions Status](https://img.shields.io/github/actions/workflow/status/RSS-Bridge/rss-bridge/tests.yml?branch=master&label=GitHub%20Actions&logo=github)](https://github.com/RSS-Bridge/rss-bridge/actions)

|||
|:-:|:-:|
|![Screenshot #1](/static/screenshot-1.png?raw=true)|![Screenshot #2](/static/screenshot-2.png?raw=true)|
|![Screenshot #3](/static/screenshot-3.png?raw=true)|![Screenshot #4](/static/screenshot-4.png?raw=true)|
|![Screenshot #5](/static/screenshot-5.png?raw=true)|![Screenshot #6](/static/screenshot-6.png?raw=true)|
|![Screenshot #7](/static/twitter-form.png?raw=true)|![Screenshot #8](/static/twitter-rasmus.png?raw=true)|

## A subset of bridges

* `YouTube` : YouTube user channel, playlist or search
* `Twitter` : Return keyword/hashtag search or user timeline
* `Telegram` : Return the latest posts from a public group
* `Reddit` : Return the latest posts from a subreddit or user
* `Filter` : Filter an existing feed url
* `Vk` : Latest posts from a user or group
* `FeedMerge` : Merge two or more existing feeds into one
* `Twitch` : Fetch the latest videos from a channel
* `ThePirateBay` : Returns the newest indexed torrents from [The Pirate Bay](https://thepiratebay.se/) with keywords

And [many more](bridges/), thanks to the community!

[Full documentation](https://rss-bridge.github.io/rss-bridge/index.html)

Check out RSS-Bridge right now on https://rss-bridge.org/bridge01 or find another
[public instance](https://rss-bridge.github.io/rss-bridge/General/Public_Hosts.html).

## Tutorial

RSS-Bridge requires php 7.4 (or higher).

### Install with git:

```bash
cd /var/www
git clone https://github.com/RSS-Bridge/rss-bridge.git

# Give the http user write permission to the cache folder
chown www-data:www-data /var/www/rss-bridge/cache

# Optionally copy over the default config file
cp config.default.ini.php config.ini.php
```

Example config for nginx:

```nginx
# /etc/nginx/sites-enabled/rssbridge
server {
    listen 80;
    server_name example.com;
    root /var/www/rss-bridge;
    index index.php;

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_read_timeout 60s;
        fastcgi_pass unix:/run/php/php-fpm.sock;
    }
}
```

### Install with Docker:

Install by using docker image from Docker Hub:

```bash
# Create container
docker create --name=rss-bridge --publish 3000:80 rssbridge/rss-bridge

# Start container
docker start rss-bridge
```

Browse http://localhost:3000/

Install by locally building the image:

```bash
# Build image from Dockerfile
docker build -t rss-bridge .

# Create container
docker create --name rss-bridge --publish 3000:80 rss-bridge

# Start the container
docker start rss-bridge
```

Browse http://localhost:3000/

#### Install with docker-compose

Create a `docker-compose.yml` file locally with with the following content:
```yml
version: '2'
services:
  rss-bridge:
    image: rssbridge/rss-bridge:latest
    volumes:
      - </local/custom/path>:/config
    ports:
      - 3000:80
    restart: unless-stopped
```

Then launch with `docker-compose`:

```bash
docker-compose up
```

Browse http://localhost:3000/

### Alternative installation methods

[![Deploy on Scalingo](https://cdn.scalingo.com/deploy/button.svg)](https://my.scalingo.com/deploy?source=https://github.com/sebsauvage/rss-bridge)
[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)
[![Deploy to Cloudron](https://cloudron.io/img/button.svg)](https://www.cloudron.io/store/com.rssbridgeapp.cloudronapp.html)

The Heroku quick deploy currently does not work. It might possibly work if you fork this repo and
modify the `repository` in `scalingo.json`. See https://github.com/RSS-Bridge/rss-bridge/issues/2688

Learn more in
[Installation](https://rss-bridge.github.io/rss-bridge/For_Hosts/Installation.html).

## How-to

### How to create a new bridge from scratch

Create the new bridge in e.g. `bridges/BearBlogBridge.php`:

```php
<?php

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

### How to enable all bridges

    enabled_bridges[] = *

### How to enable some bridges

```
enabled_bridges[] = TwitchBridge
enabled_bridges[] = GettrBridge
```

### How to enable debug mode

    enable_debug_mode = true

Learn more in [debug mode](https://rss-bridge.github.io/rss-bridge/For_Developers/Debug_mode.html).

### How to create a new output format

[Create a new format](https://rss-bridge.github.io/rss-bridge/Format_API/index.html).

## Explanation

We are RSS-Bridge community, a group of developers continuing the project initiated by sebsauvage,
webmaster of
[sebsauvage.net](https://sebsauvage.net), author of
[Shaarli](https://sebsauvage.net/wiki/doku.php?id=php:shaarli) and
[ZeroBin](https://sebsauvage.net/wiki/doku.php?id=php:zerobin).

See [CONTRIBUTORS.md](CONTRIBUTORS.md)

RSS-Bridge uses caching to prevent services from banning your server for repeatedly updating feeds.
The specific cache duration can be different between bridges. Cached files are deleted automatically after 24 hours.

RSS-Bridge allows you to take full control over which bridges are displayed to the user.
That way you can host your own RSS-Bridge service with your favorite collection of bridges!


## Reference

### FeedItem properties

```php
    $item = [
        'uri' => 'https://example.com/blog/hello',
        'title' => 'Hello world',
        // Publication date in unix timestamp
        'timestamp' => 1668706254,
        'author' => 'Alice',
        'content' => 'Here be item content',
        'enclosures' => [
            'https://example.com/foo.png',
            'https://example.com/bar.png'
        ],
        'categories' => [
            'news',
            'tech',
        ],
        // Globally unique id
        'uid' => 'e7147580c8747aad',
    ]
```

### Output formats:

* `Atom` : Atom feed, for use in feed readers
* `Html` : Simple HTML page
* `Json` : JSON, for consumption by other applications
* `Mrss` : MRSS feed, for use in feed readers
* `Plaintext` : Raw text, for consumption by other applications

### Licenses

The source code for RSS-Bridge is [Public Domain](UNLICENSE).

RSS-Bridge uses third party libraries with their own license:

  * [`Parsedown`](https://github.com/erusev/parsedown) licensed under the [MIT License](https://opensource.org/licenses/MIT)
  * [`PHP Simple HTML DOM Parser`](https://simplehtmldom.sourceforge.io/docs/1.9/index.html) licensed under the [MIT License](https://opensource.org/licenses/MIT)
  * [`php-urljoin`](https://github.com/fluffy-critter/php-urljoin) licensed under the [MIT License](https://opensource.org/licenses/MIT)
  * [`Laravel framework`](https://github.com/laravel/framework/) licensed under the [MIT License](https://opensource.org/licenses/MIT)

## Rant

*Dear so-called "social" websites.*

Your catchword is "share", but you don't want us to share. You want to keep us within your walled gardens. That's why you've been removing RSS links from webpages, hiding them deep on your website, or removed feeds entirely, replacing it with crippled or demented proprietary API. **FUCK YOU.**

You're not social when you hamper sharing by removing feeds. You're happy to have customers creating content for your ecosystem, but you don't want this content out - a content you do not even own. Google Takeout is just a gimmick. We want our data to flow, we want RSS or Atom feeds.

We want to share with friends, using open protocols: RSS, Atom, XMPP, whatever. Because no one wants to have *your* service with *your* applications using *your* API force-feeding them. Friends must be free to choose whatever software and service they want.

We are rebuilding bridges you have willfully destroyed.

Get your shit together: Put RSS/Atom back in.
