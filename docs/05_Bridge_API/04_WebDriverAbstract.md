`WebDriverAbstract` extends [`BridgeAbstract`](./02_BridgeAbstract.md) and adds functionality for generating feeds
from active websites that use XMLHttpRequest (XHR) to load content and / or JavaScript to
modify content.
It highly depends on the php-webdriver library which offers Selenium WebDriver bindings for PHP.

- https://github.com/php-webdriver/php-webdriver (Project Repository)
- https://php-webdriver.github.io/php-webdriver/latest/ (API)

Please note that this class is intended as a solution for websites _that cannot be covered
by the other classes_. The WebDriver starts a browser and is therefore very resource-intensive.

# Configuration

You need a running WebDriver to use bridges that depend on `WebDriverAbstract`.
The easiest way is to start the Selenium server from the project of the same name:
```
docker run -d -p 4444:4444 --shm-size="2g" docker.io/selenium/standalone-chrome:latest
```

- https://github.com/SeleniumHQ/docker-selenium

With these parameters only one browser window can be started at a time.
On a multi-user site, Selenium Grid should be used
and the number of sessions should be adjusted to the number of processor cores.

Finally, the `config.ini.php` file must be adjusted so that the WebDriver
can find the Selenium server:
```
[webdriver]

selenium_server_url = "http://localhost:4444"
```

# Development

While you are programming a new bridge, it is easier to start a local WebDriver because then you can see what is happening and where the errors are. I've also had good experience recording the process with a screen video to find any timing problems.

```
chromedriver --port=4444
```

- https://chromedriver.chromium.org/

If you start rss-bridge from a container, then Chrome driver is only accessible
if you call it with the `--allowed-ips` option so that it binds to all network interfaces.

```
chromedriver --port=4444 --allowed-ips=192.168.1.42
```

The **most important rule** is that after an event such as loading the web page
or pressing a button, you often have to explicitly wait for the desired elements to appear.

A simple example is the bridge `ScalableCapitalBlogBridge.php`.
A more complex and relatively complete example is the bridge `GULPProjekteBridge.php`.

# Template

Use this template to create your own bridge.

```PHP
<?php

class MyBridge extends WebDriverAbstract
{
    const NAME = 'My Bridge';
    const URI = 'https://www.example.org';
    const DESCRIPTION = 'Further description';
    const MAINTAINER = 'your name';

    public function collectData()
    {
        parent::collectData();

        try {
            // TODO
        } finally {
            $this->cleanUp();
        }
    }
}

```