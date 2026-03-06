<?php

declare(strict_types=1);

class GitHubReleaseBridge extends BridgeAbstract
{
    const NAME = 'GitHub Releases';
    const URI = 'https://github.com';
    const DESCRIPTION = 'Returns releases for a GitHub repository (excludes tag-only entries)';
    const MAINTAINER = 'kiliankoe';
    const CACHE_TIMEOUT = 3600;

    const CONFIGURATION = [
        'token' => [
            'required' => false,
        ],
    ];

    const PARAMETERS = [[
        'owner' => [
            'name' => 'Owner',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'RSS-Bridge',
            'title' => 'GitHub user or organization'
        ],
        'repo' => [
            'name' => 'Repository',
            'type' => 'text',
            'required' => true,
            'exampleValue' => 'rss-bridge',
            'title' => 'GitHub repository name'
        ],
        'pre_release' => [
            'name' => 'Include pre-releases',
            'type' => 'checkbox',
            'title' => 'Include pre-releases in the feed'
        ],
    ]];

    public function collectData()
    {
        $owner = $this->getInput('owner');
        $repo = $this->getInput('repo');
        $url = sprintf('https://api.github.com/repos/%s/%s/releases', urlencode($owner), urlencode($repo));

        $headers = [
            'Accept: application/vnd.github+json',
            'User-Agent: rss-bridge',
        ];
        $token = $this->getOption('token');
        if ($token) {
            $headers[] = 'Authorization: token ' . $token;
        }

        $json = getContents($url, $headers);
        $releases = json_decode($json, true);

        if (!is_array($releases)) {
            throwServerException('Unable to parse JSON response from GitHub API');
        }

        $includePrereleases = $this->getInput('pre_release');

        foreach ($releases as $release) {
            if ($release['draft']) {
                continue;
            }

            if ($release['prerelease'] && !$includePrereleases) {
                continue;
            }

            $title = $release['name'];
            if (empty($title)) {
                $title = $release['tag_name'];
            }

            $content = '';
            if (!empty($release['body'])) {
                $content = markdownToHtml($release['body']);
            }

            $enclosures = [];
            if (!empty($release['assets'])) {
                foreach ($release['assets'] as $asset) {
                    if (!empty($asset['browser_download_url'])) {
                        $enclosures[] = $asset['browser_download_url'];
                    }
                }
            }

            $this->items[] = [
                'title' => $title,
                'uri' => $release['html_url'],
                'content' => $content,
                'timestamp' => $release['published_at'],
                'author' => $release['author']['login'] ?? '',
                'uid' => $release['tag_name'],
                'enclosures' => $enclosures,
            ];
        }
    }

    public function getName()
    {
        $owner = $this->getInput('owner');
        $repo = $this->getInput('repo');
        if ($owner && $repo) {
            return 'Release notes from ' . $owner . '/' . $repo;
        }
        return parent::getName();
    }

    public function getURI()
    {
        $owner = $this->getInput('owner');
        $repo = $this->getInput('repo');
        if ($owner && $repo) {
            return self::URI . '/' . $owner . '/' . $repo . '/releases';
        }
        return parent::getURI();
    }

    public function detectParameters($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) === false) {
            return null;
        }

        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        if ($host !== 'github.com' && $host !== 'www.github.com') {
            return null;
        }

        $path = $parsed['path'] ?? '';
        // Match /owner/repo/releases, /owner/repo/releases.atom, or /owner/repo/tags
        if (preg_match('#^/([^/]+)/([^/]+)/(releases(?:\.atom)?|tags)$#', $path, $matches)) {
            return [
                'owner' => $matches[1],
                'repo' => $matches[2],
            ];
        }

        return null;
    }
}
