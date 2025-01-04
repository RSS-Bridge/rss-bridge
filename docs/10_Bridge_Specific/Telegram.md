# TelegramBridge

By default, it fetches a single page with up to 20 messages.

To increase this limit, tweak the `max_pages` config:

```ini
[TelegramBridge]

; Fetch a maximum of 3 pages (requires 3 http requests)
max_pages = 3
```
