FurAffinityBridge
===============
By default this bridge will only return submissions that are rated "General" and are public.

To unlock the ability to load submissions that require an account to view or are rated "Mature" and higher, you must set the following in `config.ini.php` with cookies from an existing FurAffinity account with the desired maturity ratings enabled in [Account Settings](https://www.furaffinity.net/controls/settings/).

```
[FurAffinityBridge]
aCookie = "your-a-cookie-value-here" ; from cookie "a"
bCookie = "your-b-cookie-value-here" ; from cookie "b"
```

To confirm the bridge is authenticated, the name of the authenticating account will be shown in the bridge's name once the bridge has been used at least once. (Example: `user's FurAffinity Bridge`)
