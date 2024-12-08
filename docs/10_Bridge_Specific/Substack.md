# SubstackBridge

[Substack](https://substack.com) provides RSS feeds at `/feed` path, e.g., https://newsletter.pragmaticengineer.com/feed/. However, these feeds have two problems, addressed by this bridge:
- They use RSS 2.0 with the draft [content extension](https://web.resource.org/rss/1.0/modules/content/), which isn't supported by some readers;
- They don't have the full content for paywalled posts.

Retrieving the full content is only possible _with an active subscription to the blog_. If you have one, Substack will return the full feed if it's fetched with the right set of cookies. Figuring out whether it's the intended behaviour is left as an exercise for the reader.

To obtain the session cookie, authorize at https://substack.com/, open DevTools, go to Application -> Cookies -> https://substack.com, copy the value of `substack.sid` and paste it to the RSS bridge config:

```
[SubstackBridge]
sid = "<your-sid>"
```

Authorization sometimes requires CAPTCHA, hence this operation is manual. The cookie lives for three months.

After you've done this, the bridge should return full feeds for your subscriptions.
