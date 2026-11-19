<?php

class RedditClient
{
    private const LEGACY_API_URI = 'https://old.reddit.com';
    private const OAUTH_API_URI = 'https://oauth.reddit.com';
    private const OAUTH_TOKEN_KEY = 'reddit_oauth_token';
    private const VERSION = 'v0.0.3';
    private const USER_AGENT = 'rss-bridge ' . self::VERSION . ' (https://github.com/RSS-Bridge/rss-bridge)';

    private CacheInterface $cache;
    private ?string $appId;
    private ?string $appSecret;

    public function __construct(CacheInterface $cache, ?string $appId = null, ?string $appSecret = null)
    {
        $this->cache = $cache;
        $this->appId = $appId === '' ? null : $appId;
        $this->appSecret = $appSecret === '' ? null : $appSecret;
    }

    public function search(array $parameters): Response
    {
        if ($this->appId === null || $this->appSecret === null) {
            return $this->searchUnauthenticated($parameters);
        }
        return $this->searchAuthenticated($parameters);
    }

    private function searchUnauthenticated(array $parameters): Response
    {
        $url = self::createSearchUrl(self::LEGACY_API_URI, $parameters);
        return getContents($url, ['User-Agent: ' . self::USER_AGENT], [], true);
    }

    private function searchAuthenticated(array $parameters): Response
    {
        $url = self::createSearchUrl(self::OAUTH_API_URI, $parameters);
        try {
            return $this->requestAuthenticated($url);
        } catch (HttpException $e) {
            if ($e->getCode() !== 401 && $e->getCode() !== 403) {
                throw $e;
            }
            $this->cache->delete(self::OAUTH_TOKEN_KEY);
            return $this->requestAuthenticated($url);
        }
    }

    private function requestAuthenticated(string $url): Response
    {
        $headers = [
            'User-Agent: ' . self::USER_AGENT,
            'Authorization: Bearer ' . $this->getAccessToken(),
        ];
        return getContents($url, $headers, [], true);
    }

    private function getAccessToken(): string
    {
        $cachedToken = $this->cache->get(self::OAUTH_TOKEN_KEY);
        if ($cachedToken) {
            return $cachedToken;
        }

        $headers = [
            'User-Agent: ' . self::USER_AGENT,
            'Authorization: Basic ' . base64_encode((string) $this->appId . ':' . (string) $this->appSecret),
        ];
        $data = [
            'grant_type' => 'client_credentials',
            'scope' => 'read',
        ];
        $curlopts = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
        ];
        $response = getContents('https://www.reddit.com/api/v1/access_token', $headers, $curlopts);

        $data = Json::decode($response, false);
        $token = $data->access_token;
        if (!isset($token)) {
            throw new \Exception('Failed to obtain Reddit OAuth access token: ' . $response);
        }
        $expiresIn = $data->expires_in ?? 3600;
        $this->cache->set(self::OAUTH_TOKEN_KEY, $token, $expiresIn - 60);
        return $token;
    }

    private static function createSearchUrl(string $apiUri, array $parameters): string
    {
        return $apiUri . '/search.json?' . http_build_query($parameters);
    }
}
