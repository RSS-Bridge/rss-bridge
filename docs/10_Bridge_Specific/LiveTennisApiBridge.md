# LiveTennisApiBridge

[Live Tennis API](https://livetennisapi.com) is a commercial tennis data api. This bridge needs a key,
so it does nothing until one is configured.

Get a key (the free tier needs no card), then add it to your `config.ini.php`:

```ini
[LiveTennisApiBridge]
api_key = "<your key>"
```

The environment variable `RSSBRIDGE_LiveTennisApiBridge_api_key` works too, which is usually easier in
Docker. With no key the bridge reports a configuration error naming the section and the key; no other
bridge is affected.

The key is sent in the `X-API-Key` header, never in the query string, so it stays out of access logs,
the http cache key and error messages.

## Request budget

The free tier allows **30 requests a minute and 100 a day**, and one feed fetch costs exactly one
request. `CACHE_TIMEOUT` is therefore 900 seconds (15 minutes): a feed served out of that cache costs
at most 96 requests a day however often a reader polls it.

Each distinct set of feed parameters is cached separately, so four subscriptions on one free key cost
four times as much. On a free key, subscribe to one or two variants, or raise the cache timeout.

## Feeds

| Feed | What it asks for |
|------|------------------|
| Live matches | matches in play right now |
| Today, still to start | today's (UTC) scheduled matches that have not started |
| Today, finished | today's (UTC) finished matches — **needs a paid plan** |
| Upcoming fixtures | the next scheduled fixtures, earliest first |

"Today, finished" reads the completed-match listing, which is part of the paid history product. On a
free key it returns a "not included in the plan" error rather than an empty feed.

The tour filter covers each tour's singles and doubles draws. The draw filter is a separate axis;
matches whose draw the feed never stated — team ties such as Davis Cup, where one event type covers
both singles and doubles rubbers — match neither `singles` nor `doubles`.

The player filter is applied to the page that was fetched, because the api's own player filter takes
numeric player ids rather than names and resolving a name would cost a second request. Raise the
limit if you filter on a name and expect matches further down the slate.

## Items

One item per match. The item link points at the match's api resource
(`/api/public/v1/matches/{id}`), because the vendor publishes no per-match web page; opening it in a
browser needs your key. The content lists the tournament and round, surface, draw, format, status,
sets, the games in each set, the game in progress, who is serving, whether the receiver is a point
from the break, and both players with country and current ranking where known.
