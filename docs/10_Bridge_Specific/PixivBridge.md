PixivBridge
===============

# Image proxy
As Pixiv requires images to be loaded with the `Referer "https://www.pixiv.net/"` header set, caching or image proxy is required to use this bridge.

To turn off image caching, set the `proxy_url` value in this bridge's configuration section of `config.ini.php` to the url of the proxy. The bridge will then use the proxy in this format (essentially replacing `https://i.pximg.net` with the proxy): 

Before: `https://i.pximg.net/img-original/img/0000/00/00/00/00/00/12345678_p0.png`

After: `https://proxy.example.com/img-original/img/0000/00/00/00/00/00/12345678_p0.png`

```
proxy_url = "https://proxy.example.com"
```

# Authentication
Authentication is required to view and search R-18+ and non-public images. To enable this, set the following in this bridge's configuration in `config.ini.php`.

```
; from cookie "PHPSESSID". Recommend to get in incognito browser. 
cookie = "00000000_hashedsessionidhere"
```