This section is directed at **hosts** and **server administrators**.

To install RSS-Bridge, please follow the [installation instructions](../03_For_Hosts/01_Installation.md). You must have access to a web server with a working PHP environment!

RSS-Bridge comes with a large amount of bridges. Only few bridges are enabled by default. Unlock more bridges by adding them to the [whitelist](../03_For_Hosts/05_Whitelisting.md).

Some bridges could be implemented more efficiently by actually using proprietary APIs, but there are reasons against it:

- RSS-Bridge exists in the first place to NOT use APIs. See [the rant](https://github.com/RSS-Bridge/rss-bridge/blob/master/README.md#Rant)

- APIs require private keys that could be stored on servers running RSS-Bridge, which is a security concern, involves complex authorizations for inexperienced users and could cause harm (when using paid services for example). In a closed environment (a server only you use for yourself) however you might be interested in using them anyway. So, check [this](https://github.com/RSS-Bridge/rss-bridge/pull/478/files) possible implementation of an anti-captcha solution.