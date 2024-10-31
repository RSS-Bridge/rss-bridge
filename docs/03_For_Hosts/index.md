This section is directed at **hosts** and **server administrators**.

RSS-Bridge comes with a large amount of bridges.

Some bridges could be implemented more efficiently by actually using proprietary APIs,
but there are reasons against it:

- RSS-Bridge exists in the first place to NOT use APIs.
  See [the rant](https://github.com/RSS-Bridge/rss-bridge/blob/master/README.md#Rant).

- APIs require private keys that could be stored on servers running RSS-Bridge,
  which is a security concern, involves complex authorizations for inexperienced users and could cause harm (when using paid services for example). In a closed environment (a server only you use for yourself) however you might be interested in using them anyway. So, check [this](https://github.com/RSS-Bridge/rss-bridge/pull/478/files) possible implementation of an anti-captcha solution.
