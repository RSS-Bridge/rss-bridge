# RSS-Bridge

![RSS-Bridge](static/logo_600px.png)

RSS-Bridge is a PHP web application.

It generates web feeds for websites that don't have one.

Officially hosted instance: https://rss-bridge.org/bridge01/

IRC channel #rssbridge at https://libera.chat/

[Full documentation](https://rss-bridge.github.io/rss-bridge/index.html)

Alternatively find another
[public instance](https://rss-bridge.github.io/rss-bridge/General/Public_Hosts.html).

Requires minimum PHP 7.4.


[![LICENSE](https://img.shields.io/badge/license-UNLICENSE-blue.svg)](UNLICENSE)
[![GitHub release](https://img.shields.io/github/release/rss-bridge/rss-bridge.svg?logo=github)](https://github.com/rss-bridge/rss-bridge/releases/latest)
[![irc.libera.chat](https://img.shields.io/badge/irc.libera.chat-%23rssbridge-blue.svg)](https://web.libera.chat/#rssbridge)
[![Actions Status](https://img.shields.io/github/actions/workflow/status/RSS-Bridge/rss-bridge/tests.yml?branch=master&label=GitHub%20Actions&logo=github)](https://github.com/RSS-Bridge/rss-bridge/actions)

|||
|:-:|:-:|
|![Screenshot #1](/static/screenshot-1.png?raw=true)|![Screenshot #2](/static/screenshot-2.png?raw=true)|
|![Screenshot #3](/static/screenshot-3.png?raw=true)|![Screenshot #4](/static/screenshot-4.png?raw=true)|
|![Screenshot #5](/static/screenshot-5.png?raw=true)|![Screenshot #6](/static/screenshot-6.png?raw=true)|

## A subset of bridges (15/447)

* `CssSelectorBridge`: [Scrape out a feed using CSS selectors](https://rss-bridge.org/bridge01/#bridge-CssSelectorBridge)
* `FeedMergeBridge`: [Combine multiple feeds into one](https://rss-bridge.org/bridge01/#bridge-FeedMergeBridge)
* `FeedReducerBridge`: [Reduce a noisy feed by some percentage](https://rss-bridge.org/bridge01/#bridge-FeedReducerBridge)
* `FilterBridge`: [Filter a feed by excluding/including items by keyword](https://rss-bridge.org/bridge01/#bridge-FilterBridge)
* `GettrBridge`: [Fetches the latest posts from a GETTR user](https://rss-bridge.org/bridge01/#bridge-GettrBridge)
* `MastodonBridge`: [Fetches statuses from a Mastodon (ActivityPub) instance](https://rss-bridge.org/bridge01/#bridge-MastodonBridge)
* `RedditBridge`: [Fetches posts from a user/subredit (with filtering options)](https://rss-bridge.org/bridge01/#bridge-RedditBridge)
* `RumbleBridge`: [Fetches channel/user videos](https://rss-bridge.org/bridge01/#bridge-RumbleBridge)
* `SoundcloudBridge`: [Fetches music by username](https://rss-bridge.org/bridge01/#bridge-SoundcloudBridge)
* `TelegramBridge`: [Fetches posts from a public channel](https://rss-bridge.org/bridge01/#bridge-TelegramBridge)
* `ThePirateBayBridge:` [Fetches torrents by search/user/category](https://rss-bridge.org/bridge01/#bridge-ThePirateBayBridge)
* `TikTokBridge`: [Fetches posts by username](https://rss-bridge.org/bridge01/#bridge-TikTokBridge)
* `TwitchBridge`: [Fetches videos from channel](https://rss-bridge.org/bridge01/#bridge-TwitchBridge)
* `XPathBridge`: [Scrape out a feed using XPath expressions](https://rss-bridge.org/bridge01/#bridge-XPathBridge)
* `YoutubeBridge`: [Fetches videos by username/channel/playlist/search](https://rss-bridge.org/bridge01/#bridge-YoutubeBridge)
* `YouTubeCommunityTabBridge`: [Fetches posts from a channel's Posts tab](https://rss-bridge.org/bridge01/#bridge-YouTubeCommunityTabBridge)

## Tutorial

### How to install on traditional shared web hosting

RSS-Bridge can basically be unzipped into a web folder. Should be working instantly.

Latest zip:
https://github.com/RSS-Bridge/rss-bridge/archive/refs/heads/master.zip (2MB)

### How to install on Debian 12 (nginx + php-fpm)

These instructions have been tested on a fresh Debian 12 VM from Digital Ocean (1vcpu-512mb-10gb, 5 USD/month).

```shell
timedatectl set-timezone Europe/Oslo

apt install git nginx php8.2-fpm php-mbstring php-simplexml php-curl php-intl

# Create a user account
useradd --shell /bin/bash --create-home rss-bridge

cd /var/www

# Create folder and change its ownership to rss-bridge
mkdir rss-bridge && chown rss-bridge:rss-bridge rss-bridge/

# Become rss-bridge
su rss-bridge

# Clone master branch into existing folder
git clone https://github.com/RSS-Bridge/rss-bridge.git rss-bridge/
cd rss-bridge

# Copy over the default config (OPTIONAL)
cp -v config.default.ini.php config.ini.php

# Recursively give full permissions to user/owner
chmod 700 --recursive ./

# Give read and execute to others on folder ./static
chmod o+rx ./ ./static

# Recursively give give read to others on folder ./static
chmod o+r --recursive ./static
```

Nginx config:

```nginx
# /etc/nginx/sites-enabled/rss-bridge.conf

server {
    listen 80;

    # TODO: change to your own server name
    server_name example.com;

    access_log /var/log/nginx/rss-bridge.access.log;
    error_log /var/log/nginx/rss-bridge.error.log;
    log_not_found off;

    # Intentionally not setting a root folder

    # Static content only served here
    location /static/ {
        alias /var/www/rss-bridge/static/;
    }

    # Pass off to php-fpm only when location is EXACTLY == /
    location = / {
        root /var/www/rss-bridge/;
        include snippets/fastcgi-php.conf;
        fastcgi_read_timeout 45s;
        fastcgi_pass unix:/run/php/rss-bridge.sock;
    }

    # Reduce log noise
    location = /favicon.ico {
        access_log off;
    }

    # Reduce log noise
    location = /robots.txt {
        access_log off;
    }
}
```

PHP FPM pool config:
```ini
; /etc/php/8.2/fpm/pool.d/rss-bridge.conf

[rss-bridge]

user = rss-bridge
group = rss-bridge

listen = /run/php/rss-bridge.sock

listen.owner = www-data
listen.group = www-data

; Create 10 workers standing by to serve requests
pm = static
pm.max_children = 10

; Respawn worker after 500 requests (workaround for memory leaks etc.)
pm.max_requests = 500
```

PHP ini config:
```ini
; /etc/php/8.2/fpm/conf.d/30-rss-bridge.ini

max_execution_time = 15
memory_limit = 64M
```

Restart fpm and nginx:

```shell
# Lint and restart php-fpm
php-fpm8.2 -t && systemctl restart php8.2-fpm

# Lint and restart nginx
nginx -t && systemctl restart nginx
```

### How to install from Composer

Install the latest release.

```shell
cd /var/www
composer create-project -v --no-dev --no-scripts rss-bridge/rss-bridge
```

### How to install with Caddy

TODO. See https://github.com/RSS-Bridge/rss-bridge/issues/3785

### Install from Docker Hub:

Install by downloading the docker image from Docker Hub:

```bash
# Create container
docker create --name=rss-bridge --publish 3000:80 --volume $(pwd)/config:/config rssbridge/rss-bridge
```

You can put custom `config.ini.php` and bridges into `./config`.

**You must restart container for custom changes to take effect.**

See `docker-entrypoint.sh` for details.

```bash
# Start container
docker start rss-bridge
```

Browse http://localhost:3000/

### Install by locally building from Dockerfile

```bash
# Build image from Dockerfile
docker build -t rss-bridge .

# Create container
docker create --name rss-bridge --publish 3000:80 --volume $(pwd)/config:/config rss-bridge
```

You can put custom `config.ini.php` and bridges into `./config`.

**You must restart container for custom changes to take effect.**

See `docker-entrypoint.sh` for details.

```bash
# Start container
docker start rss-bridge
```

Browse http://localhost:3000/

### Install with docker-compose (using Docker Hub)

You can put custom `config.ini.php` and bridges into `./config`.

**You must restart container for custom changes to take effect.**

See `docker-entrypoint.sh` for details.

```bash
docker-compose up
```

Browse http://localhost:3000/

### Other installation methods

[![Deploy on Scalingo](https://cdn.scalingo.com/deploy/button.svg)](https://my.scalingo.com/deploy?source=https://github.com/sebsauvage/rss-bridge)
[![Deploy to Heroku](https://www.herokucdn.com/deploy/button.svg)](https://heroku.com/deploy)
[![Deploy to Cloudron](https://cloudron.io/img/button.svg)](https://www.cloudron.io/store/com.rssbridgeapp.cloudronapp.html)
[![Run on PikaPods](https://www.pikapods.com/static/run-button.svg)](https://www.pikapods.com/pods?run=rssbridge)

The Heroku quick deploy currently does not work. It might work if you fork this repo and
modify the `repository` in `scalingo.json`. See https://github.com/RSS-Bridge/rss-bridge/issues/2688

Learn more in
[Installation](https://rss-bridge.github.io/rss-bridge/For_Hosts/Installation.html).

## How-to

### How to fix "Access denied."

Output is from php-fpm. It is unable to read index.php.

    chown rss-bridge:rss-bridge /var/www/rss-bridge/index.php

### How to password-protect the instance (token)

Modify `config.ini.php`:

    [authentication]

    token = "hunter2"

### How to remove all cache items

As current user:

    bin/cache-clear

As user rss-bridge:

    sudo -u rss-bridge bin/cache-clear

As root:

    sudo bin/cache-clear

### How to remove all expired cache items

    bin/cache-prune

### How to fix "PHP Fatal error:  Uncaught Exception: The FileCache path is not writable"

```shell
# Give rss-bridge ownership
chown rss-bridge:rss-bridge -R /var/www/rss-bridge/cache

# Or, give www-data ownership
chown www-data:www-data -R /var/www/rss-bridge/cache

# Or, give everyone write permission
chmod 777 -R /var/www/rss-bridge/cache

# Or last ditch effort (CAREFUL)
rm -rf /var/www/rss-bridge/cache/ && mkdir /var/www/rss-bridge/cache/
```

### How to fix "attempt to write a readonly database"

The sqlite files (db, wal and shm) are not writeable.

    chown -v rss-bridge:rss-bridge cache/*

### How to fix "Unable to prepare statement: 1, no such table: storage"

    rm cache/*

### How to create a completely new bridge

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

### How to enable all bridges

    enabled_bridges[] = *

### How to enable some bridges

```
enabled_bridges[] = TwitchBridge
enabled_bridges[] = GettrBridge
```

### How to switch to memcached as cache backend

```
[cache]

; Cache backend: file (default), sqlite, memcached, null
type = "memcached"
```

### How to switch to sqlite3 as cache backend

    type = "sqlite"

### How to disable bridge errors (as feed items)

When a bridge fails, RSS-Bridge will produce a feed with a single item describing the error.

This way, feed readers pick it up and you are notified.

If you don't want this behaviour, switch the error output to `http`:

    [error]

    ; Defines how error messages are returned by RSS-Bridge
    ;
    ; "feed" = As part of the feed (default)
    ; "http" = As HTTP error message
    ; "none" = No errors are reported
    output = "http"

### How to accumulate errors before finally reporting it

Modify `report_limit` so that an error must occur 3 times before it is reported.

    ; Defines how often an error must occur before it is reported to the user
    report_limit = 3

The report count is reset to 0 each day.

### How to password-protect the instance (HTTP Basic Auth)

    [authentication]

    enable = true
    username = "alice"
    password = "cat"

Will typically require feed readers to be configured with the credentials.

It may also be possible to manually include the credentials in the URL:

https://alice:cat@rss-bridge.org/bridge01/?action=display&bridge=FabriceBellardBridge&format=Html

### How to create a new output format

See `formats/PlaintextFormat.php` for an example.

### How to run unit tests and linter

These commands require that you have installed the dev dependencies in `composer.json`.

Run all tests:

    ./vendor/bin/phpunit

Run a single test class:

    ./vendor/bin/phpunit --filter UrlTest

Run linter:

    ./vendor/bin/phpcs --standard=phpcs.xml --warning-severity=0 --extensions=php -p ./

https://github.com/squizlabs/PHP_CodeSniffer/wiki

### How to spawn a minimal development environment

    php -S 127.0.0.1:9001

http://127.0.0.1:9001/

## Explanation

We are RSS-Bridge community, a group of developers continuing the project initiated by sebsauvage,
webmaster of
[sebsauvage.net](https://sebsauvage.net), author of
[Shaarli](https://sebsauvage.net/wiki/doku.php?id=php:shaarli) and
[ZeroBin](https://sebsauvage.net/wiki/doku.php?id=php:zerobin).

See [CONTRIBUTORS.md](CONTRIBUTORS.md)

RSS-Bridge uses caching to prevent services from banning your server for repeatedly updating feeds.
The specific cache duration can be different between bridges.

RSS-Bridge allows you to take full control over which bridges are displayed to the user.
That way you can host your own RSS-Bridge service with your favorite collection of bridges!

Current maintainers (as of 2024): @dvikan and @Mynacol #2519

## Reference

### Feed item structure

This is the feed item structure that bridges are expected to produce.

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

### Output formats

* `Atom`: Atom feed, for use in feed readers
* `Html`: Simple HTML page
* `Json`: JSON, for consumption by other applications
* `Mrss`: MRSS feed, for use in feed readers
* `Plaintext`: Raw text, for consumption by other applications
* `Sfeed`: Text, TAB separated

### Cache backends

* `File`
* `SQLite`
* `Memcached`
* `Array`
* `Null`

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
