# MastodonBridge (aka. ActivityPub Bridge)

Certain ActivityPub implementations, such as [Mastodon](https://docs.joinmastodon.org/spec/security/#http) and [Pleroma](https://docs-develop.pleroma.social/backend/configuration/cheatsheet/#activitypub), allow instances to require requests to ActivityPub endpoints to be signed. RSS-Bridge can handle the HTTP signature header if a private key is provided, while the ActivityPub instance must be able to know the corresponding public key.

You do **not** need to configure this if the usage on your RSS-Bridge instance is limited to accessing ActivityPub instances that do not have such requirements. While the majority of ActivityPub instances don't have them at the time of writing, the situation may change in the future.

## Configuration

[This article](https://blog.joinmastodon.org/2018/06/how-to-implement-a-basic-activitypub-server/) is referenced.

1. Select a domain. It may, but does not need to, be the one RSS-Bridge is on. For all subsequent steps, replace `DOMAIN` with this domain.
2. Run the following commands on your machine:
```bash
$ openssl genrsa -out private.pem 2048
$ openssl rsa -in private.pem -outform PEM -pubout -out public.pem
```
3. Place `private.pem` in an appropriate location and note down its absolute path.
4. Serve the following page at `https://DOMAIN/.well-known/webfinger`:
```json
{
	"subject": "acct:DOMAIN@DOMAIN",
	"aliases": ["https://DOMAIN/actor"],
	"links": [{
		"rel": "self",
		"type": "application/activity+json",
		"href": "https://DOMAIN/actor"
	}]
}
```
5. Serve the following page at `https://DOMAIN/actor`, replacing the value of `publicKeyPem` with the contents of the `public.pem` file in step 2, with all line breaks substituted with `\n`:
```json
{
    "@context": [
      "https://www.w3.org/ns/activitystreams",
      "https://w3id.org/security/v1"
    ],
    "id": "https://DOMAIN/actor",
    "type": "Application",
    "inbox": "https://DOMAIN/actor/inbox",
    "preferredUsername": "DOMAIN",
    "publicKey": {
        "id": "https://DOMAIN/actor#main-key",
        "owner": "https://DOMAIN/actor",
        "publicKeyPem": "-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----\n"
    }
}
```
6. Add the following configuration in `config.ini.php` in your RSS-Bridge folder, replacing the path with the one from step 3:
```ini
[MastodonBridge]
private_key = "/absolute/path/to/your/private.pem"
key_id = "https://DOMAIN/actor#main-key"
```

## Considerations

Any ActivityPub instance your users requested content from will be able to identify requests from your RSS-Bridge instance by the domain you specified in the configuration. This also means that an ActivityPub instance may choose to block this domain should they judge your instance's usage excessive. Therefore, public instance operators should monitor for abuse and prepare to communicate with ActivityPub instance admins when necessary. You may also leave contact information as the `summary` value in the actor JSON (step 5).
