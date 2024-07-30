# EconomistWorldInBriefBridge and EconomistBridge

In May 2024, The Economist finally fixed its paywall, and it started requiring authorization. Which means you can't use this bridge unless you have an active subscription.

If you do, the way to use the bridge is to snitch a cookie:
1. Log in to The Economist
2. Open DevTools (Chrome DevTools or Firefox Developer Tools)
2. Go to https://www.economist.com/the-world-in-brief
3. In DevTools, go to the "Network" tab, there select the first request (`the-world-in-brief`) and copy the value of the `Cookie:` header from "Request Headers".

The cookie lives three months.

Once you've done this, add the cookie to your `config.ini.php`:

```
[EconomistWorldInBriefBridge]
cookie = "<value>"

[EconomistBridge]
cookie = "<value>"
```
