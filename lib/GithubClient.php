<?php

declare(strict_types=1);

/**
 * https://docs.github.com/en/rest
 * https://docs.github.com/en/rest/issues/issues
 * https://docs.github.com/en/rest/pulls/pulls
 */
class GithubClient
{
    const BASE = 'https://api.github.com';

    private CacheInterface $cache;
    private Logger $logger;
    private ?string $token;

    public function __construct(
        CacheInterface $cache,
        Logger $logger,
        ?string $token = null
    ) {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->token = $token;
    }

    public function fetchIssues(string $owner, string $repo): array
    {
        $issues = $this->fetch(sprintf('/repos/%s/%s/issues?per_page=30', $owner, $repo));

        $issues = array_filter($issues, function ($issue) {
            // Exclude pull requests
            return ! isset($issue['pull_request']);
        });

        return array_map([$this, 'map'], $issues);
    }

    public function fetchPullRequests(string $owner, string $repo): array
    {
        $pulls = $this->fetch(sprintf('/repos/%s/%s/pulls?per_page=30', $owner, $repo));

        return array_map([$this, 'map'], $pulls);
    }

    public function fetchPullRequestComments(string $owner, string $repo, int $id): array
    {
        return $this->fetchIssueComments($owner, $repo, $id);
    }

    public function fetchIssueComments(string $owner, string $repo, int $id): array
    {
        $comments = $this->fetch(sprintf('/repos/%s/%s/issues/%s/comments', $owner, $repo, $id));

        return array_reverse(array_map([$this, 'map'], $comments));
    }

    public function fetchRateLimit(): array
    {
        return $this->fetch('/rate_limit');
    }

    private function fetch(string $url): array
    {
        $cacheKey = 'github_rate_limit';
        if ($this->cache->get($cacheKey)) {
            $this->logger->info(sprintf('github: Internal github rate limit'));
            throwRateLimitException(sprintf('Internal github rate limit'));
        }

        $this->logger->info(sprintf('github: github_client->fetch(%s)', $url));

        $headers = [];
        if ($this->token) {
            $headers[] = 'Authorization: Bearer ' . $this->token;
        }

        try {
            $response = getContents(self::BASE . $url, $headers, [], true);

            $limit      = $response->getHeader('x-ratelimit-limit');
            $remaining  = $response->getHeader('x-ratelimit-remaining');
            $used       = $response->getHeader('x-ratelimit-used');
            $reset      = $response->getHeader('x-ratelimit-reset');
            $resource   = $response->getHeader('x-ratelimit-resource');

            $this->logger->info(sprintf('github: limit=%s, remaining=%s, used=%s, reset=%s', $limit, $remaining, $used, $reset));
        } catch (HttpException $e) {
            if (in_array($e->getCode(), [403, 429])) {
                $this->logger->info(sprintf('github: REAL github rate limit'));
                $this->cache->set($cacheKey, true, 60 * 60);
                throwRateLimitException(sprintf('REAL github rate limit'));
            }
            throw $e;
        }
        return Json::decode($response->getBody());
    }

    private function map(array $issue): array
    {
        return [
            'uri'           => $issue['html_url'],
            'title'         => $issue['title'] ?? null,
            'author'        => $issue['user']['login'],
            'timestamp'     => $issue['created_at'],
            'content'       => $issue['body'],
        ];
    }
}
