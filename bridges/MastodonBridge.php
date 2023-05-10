<?php

class MastodonBridge extends BridgeAbstract
{
    // This script attempts to imitiate the behaviour of a read-only ActivityPub server
    // to read the outbox.

    // Note: Most PixelFed instances have ActivityPub outbox disabled,
    // so use the official feed: https://pixelfed.instance/users/username.atom (Posts only)

    const MAINTAINER = 'Austin Huang';
    const NAME = 'ActivityPub Bridge';
    const CACHE_TIMEOUT = 900; // 15mn
    const DESCRIPTION = 'Returns recent statuses. Supports Mastodon, Pleroma and Misskey, among others. Access to
    instances that have Authorized Fetch enabled requires
    <a href="https://rss-bridge.github.io/rss-bridge/Bridge_Specific/ActivityPub_(Mastodon).html">configuration</a>.';
    const URI = 'https://mastodon.social';

    // Some Mastodon instances use Secure Mode which requires all requests to be signed.
    // You do not need this for most instances, but if you want to support every known
    // instance, then you should configure them.
    // See also https://docs.joinmastodon.org/spec/security/#http
    const CONFIGURATION = [
        'private_key' => [
            'required' => false,
        ],
        'key_id' => [
            'required' => false,
        ],
    ];

    const PARAMETERS = [[
        'canusername' => [
            'name' => 'Canonical username',
            'exampleValue' => '@sebsauvage@framapiaf.org',
            'required' => true,
        ],
        'norep' => [
            'name' => 'Without replies',
            'type' => 'checkbox',
            'title' => 'Hide replies, as determined by relations (not mentions).'
        ],
        'noboost' => [
            'name' => 'Without boosts',
            'type' => 'checkbox',
            'title' => 'Hide boosts. This will reduce loading time as RSS-Bridge fetches the boosted status from other federated instances.'
        ],
        'signaturetype' => [
            'type' => 'list',
            'name' => 'Signature Type',
            'title' => 'How to sign requests when fetching from instances.
                Defaults to "nosig" for RSS-Bridge instances that did not set up signatures.',
            'values' => [
                'Without Query (Mastodon)' => 'noquery',
                'With Query (GoToSocial)' => 'query',
                'Don\'t sign' => 'nosig',
            ],
            'defaultValue' => 'noquery'
        ],
    ]];

    public function collectData()
    {
        $user = $this->fetchAP($this->getURI());
        if (!isset($user['outbox'])) {
            throw new \Exception('Unable to find the outbox');
        }
        $content = $this->fetchAP($user['outbox']);
        if (is_array($content['first'])) { // mobilizon
            $content = $content['first'];
        } else {
            $content = $this->fetchAP($content['first']);
        }
        $items = $content['orderedItems'] ?? $content['items'];
        foreach ($items as $status) {
            $item = $this->parseItem($status);
            if ($item) {
                $this->items[] = $item;
            }
        }
    }

    protected function parseItem($content)
    {
        $item = [];
        switch ($content['type']) {
            case 'Announce': // boost
                if ($this->getInput('noboost')) {
                    return null;
                }
                // We fetch the boosted content.
                try {
                    $rtContent = $this->fetchAP($content['object']);
                    $rtUser = $this->loadCacheValue($rtContent['attributedTo'], 86400);
                    if (!isset($rtUser)) {
                        // We fetch the author, since we cannot always assume the format of the URL.
                        $user = $this->fetchAP($rtContent['attributedTo']);
                        preg_match('/https?:\/\/([a-z0-9-\.]{0,})\//', $rtContent['attributedTo'], $matches);
                        // We assume that the server name as indicated by the path is the actual server name,
                        // since using webfinger to delegate domains is not officially supported, and it only
                        // seems to work in one way.
                        $rtUser = '@' . $user['preferredUsername'] . '@' . $matches[1];
                        $this->saveCacheValue($rtContent['attributedTo'], $rtUser);
                    }
                    $item['author'] = $rtUser;
                    $item['title'] = 'Shared a status by ' . $rtUser . ': ';
                    $item = $this->parseObject($rtContent, $item);
                } catch (HttpException $e) {
                    $item['title'] = 'Shared an unreachable status: ' . $content['object'];
                    $item['content'] = $content['object'];
                    $item['uri'] = $content['object'];
                }
                break;
            case 'Note': // frendica posts
                if ($this->getInput('norep') && isset($content['inReplyTo'])) {
                    return null;
                }
                $item['title'] = '';
                $item['author'] = $this->getInput('canusername');
                $item = $this->parseObject($content, $item);
                break;
            case 'Create': // posts
                if ($this->getInput('norep') && isset($content['object']['inReplyTo'])) {
                    return null;
                }
                $item['title'] = '';
                $item['author'] = $this->getInput('canusername');
                $item = $this->parseObject($content['object'], $item);
                break;
            default:
                return null;
        }
        $item['timestamp'] = $content['published'] ?? $item['timestamp'];
        $item['uid'] = $content['id'];
        return $item;
    }

    protected function parseObject($object, $item)
    {
        // If object is a link to another object, fetch it
        if (is_string($object)) {
            $object = $this->fetchAP($object);
        }

        $item['content'] = $object['content'];
        $strippedContent = strip_tags(str_replace('<br>', ' ', $object['content']));

        if (isset($object['name'])) {
            $item['title'] = $object['name'];
        } else if (mb_strlen($strippedContent) > 75) {
            $contentSubstring = mb_substr($strippedContent, 0, mb_strpos(wordwrap($strippedContent, 75), "\n"));
            $item['title'] .= $contentSubstring . '...';
        } else {
            $item['title'] .= $strippedContent;
        }
        $item['uri'] = $object['id'];
        $item['timestamp'] = $object['published'];

        if (!isset($object['attachment'])) {
            return $item;
        }

        if (isset($object['attachment']['url'])) {
            // Normalize attachment (turn single attachment into array)
            $object['attachment'] = [$object['attachment']];
        }

        foreach ($object['attachment'] as $attachment) {
            // Only process REMOTE pictures (prevent xss)
            if (
                $attachment['mediaType']
                && preg_match('/^image\//', $attachment['mediaType'], $match)
                && preg_match('/^http(s|):\/\//', $attachment['url'], $match)
            ) {
                $item['content'] = $item['content'] . '<br /><img ';
                if ($attachment['name']) {
                    $item['content'] .= sprintf('alt="%s" ', $attachment['name']);
                }
                $item['content'] .= sprintf('src="%s" />', $attachment['url']);
            }
        }
        return $item;
    }

    public function getName()
    {
        if ($this->getInput('canusername')) {
            return $this->getInput('canusername');
        }
        return parent::getName();
    }

    private function getInstance()
    {
        preg_match('/^@[a-zA-Z0-9_]+@(.+)/', $this->getInput('canusername'), $matches);
        return $matches[1];
    }

    private function getUsername()
    {
        preg_match('/^@([a-zA-Z_0-9_]+)@.+/', $this->getInput('canusername'), $matches);
        return $matches[1];
    }

    public function getURI()
    {
        if ($this->getInput('canusername')) {
            // We parse webfinger to make sure the URL is correct. This is mostly because
            // MissKey uses user ID instead of the username in the endpoint, domain delegations,
            // and also to be compatible with future ActivityPub implementations.
            $resource = 'acct:' . $this->getUsername() . '@' . $this->getInstance();
            $webfingerUrl = 'https://' . $this->getInstance() . '/.well-known/webfinger?resource=' . $resource;
            $webfingerHeader = [
                'Accept: application/jrd+json'
            ];
            $webfinger = json_decode(getContents($webfingerUrl, $webfingerHeader), true);
            foreach ($webfinger['links'] as $link) {
                if ($link['type'] === 'application/activity+json') {
                    return $link['href'];
                }
            }
        }

        return parent::getURI();
    }

    protected function fetchAP($url)
    {
        $d = new DateTime();
        $d->setTimezone(new DateTimeZone('GMT'));
        $date = $d->format('D, d M Y H:i:s e');

        // GoToSocial expects the query string to be included when
        // building the url to sign
        // @see https://github.com/superseriousbusiness/gotosocial/issues/107#issuecomment-1188289857
        $regex = [
            // Include query string when parsing URL
            'query' => '/https?:\/\/([a-z0-9-\.]{0,})(\/[^#]+)/',

            // Exclude query string when parsing URL
            'noquery' => '/https?:\/\/([a-z0-9-\.]{0,})(\/[^#?]+)/',
            'nosig' => '/https?:\/\/([a-z0-9-\.]{0,})(\/[^#?]+)/',
        ];

        preg_match($regex[$this->getInput('signaturetype')], $url, $matches);
        $headers = [
            'Accept: application/activity+json',
            'Host: ' . $matches[1],
            'Date: ' . $date
        ];
        $privateKey = $this->getOption('private_key');
        $keyId = $this->getOption('key_id');
        if ($privateKey && $keyId && $this->getInput('signaturetype') !== 'nosig') {
            $pkey = openssl_pkey_get_private('file://' . $privateKey);
            $toSign = '(request-target): get ' . $matches[2] . "\nhost: " . $matches[1] . "\ndate: " . $date;
            $result = openssl_sign($toSign, $signature, $pkey, 'RSA-SHA256');
            if ($result) {
                Debug::log($toSign);
                $sig = 'Signature: keyId="' . $keyId . '",headers="(request-target) host date",signature="' .
                        base64_encode($signature) . '"';
                Debug::log($sig);
                array_push($headers, $sig);
            }
        }
        return json_decode(getContents($url, $headers), true);
    }
}
