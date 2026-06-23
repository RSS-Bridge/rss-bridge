<?php

declare(strict_types=1);

class GitHubReleaseBridge extends BridgeAbstract
{
    const NAME = 'GitHub Releases';
    const URI = 'https://github.com';
    const DESCRIPTION = 'Returns releases for a GitHub repository (excludes tag-only entries)';
    const MAINTAINER = 'LordArrin';
    const CACHE_TIMEOUT = 3600;

    const CONFIGURATION = ['token' => ['required' => false]];

    const PARAMETERS = [[
        'owner' => [
              'name' => 'Owner',
              'type' => 'text',
              'required' => true,
              'title' => 'The name of the repo owner (e.g. RSS-Bridge from https://github.com/RSS-Bridge/rss-bridge)'
        ],
        'repo' => [
              'name' => 'Repository',
              'type' => 'text',
              'required' => true,
              'title' => 'Repo name (e.g. rss-bridge from https://github.com/RSS-Bridge/rss-bridge)'
        ],
        'pre_release' => [
              'name' => 'Include pre-releases',
              'type' => 'checkbox',
              'title' => 'Check this box to hide the pre-releases from feed'
        ],
        'hide_assets' => [
              'name' => 'Hide attachments',
              'type' => 'checkbox',
              'title' => 'Check this box to hide attachments from feed items.'
        ],
        'limit' => [
              'name' => 'Posts limit',
              'type' => 'number',
              'defaultValue' => 10
        ],
    ]];

    private const ALLOWED_TAGS = '<div><a><p><ul><ol><li><strong><em><code><pre><blockquote>'
        . '<h1><h2><h3><h4><h5><h6><br><hr><img><table><thead><tbody><tr><th><td>'
        . '<del><details><summary>';

    private const CSS = [
        'wrapper'      => 'font-size:14px; line-height:1.6; word-wrap:break-word;',
        'alert_base'   => 'padding-left:12px; margin:8px 0;',
        'alert_border' => 'border-left:4px solid %s;',
        'ul'           => 'list-style-type:disc; padding-left:24px;',
        'ol'           => 'list-style-type:decimal; padding-left:24px;',
        'alerts'       => [
            'NOTE' => '#0969da', 'TIP' => '#1a7f37', 'IMPORTANT' => '#8250df',
            'WARNING' => '#9a6700', 'CAUTION' => '#cf222e',
        ],
    ];

    public function collectData()
    {
        $owner = $this->getInput('owner');
        $repo = $this->getInput('repo');

        $url = sprintf('https://api.github.com/repos/%s/%s/releases?per_page=100', $owner, $repo);
        $headers = ['Accept: application/vnd.github+json', 'User-Agent: rss-bridge'];
        $token = $this->getOption('token');
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        try {
            $releases = json_decode(getContents($url, $headers), true) ?: [];
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            $msg = [401 => 'Auth failed', 403 => 'Rate limit exceeded', 404 => 'Repo not found'];
            throwServerException($msg[$code] ?? ('GitHub API error (' . $code . ')'));
        }

        $includePrereleases = (bool) $this->getInput('pre_release');
        $hideAssets = (bool) $this->getInput('hide_assets');
        $limit = max(1, min(100, (int) ($this->getInput('limit') ?: 10)));

        foreach ($releases as $release) {
            if (count($this->items) >= $limit) {
                break;
            }
            if (!empty($release['draft']) || (!empty($release['prerelease']) && !$includePrereleases)) {
                continue;
            }
            $this->items[] = $this->buildFeedItem($release, $owner, $repo, $hideAssets);
        }
    }

    public function getName()
    {
        if ($this->getInput('owner') && $this->getInput('repo')) {
            return sprintf('%s/%s - Releases', $this->getInput('owner'), $this->getInput('repo'));
        }
        return parent::getName();
    }

    public function getURI()
    {
        if ($this->getInput('owner') && $this->getInput('repo')) {
            return sprintf('%s/%s/%s/releases', self::URI, $this->getInput('owner'), $this->getInput('repo'));
        }
        return parent::getURI();
    }

    public function detectParameters($url)
    {
        if (!is_string($url)) {
            return null;
        }
        $parsed = parse_url($url);
        if (!in_array($parsed['host'] ?? '', ['github.com', 'www.github.com'], true)) {
            return null;
        }
        if (preg_match('#^/([^/]+)/([^/]+)(?:/(?:releases|tags))?$#', $parsed['path'] ?? '', $m)) {
            return ['owner' => $m[1], 'repo' => $m[2]];
        }
        return null;
    }

    private function buildFeedItem(array $release, string $owner, string $repo, bool $hideAssets): array
    {
        $title = $release['name'] ?: ($release['tag_name'] ?? 'Untitled');
        $content = !empty($release['body']) ? $this->processMarkdown((string) $release['body'], $owner, $repo) : '';

        $enclosures = [];
        if (!$hideAssets) {
            foreach ($release['assets'] ?? [] as $asset) {
                if (!empty($asset['browser_download_url'])) {
                    $enclosures[] = $asset['browser_download_url'];
                }
            }
        }

        $ts = strtotime($release['published_at'] ?? $release['created_at'] ?? 'now') ?: time();

        return [
            'title'      => $title,
            'uri'        => $release['html_url'] ?? '',
            'content'    => $content,
            'timestamp'  => $ts,
            'author'     => $release['author']['login'] ?? '',
            'uid'        => $release['tag_name'] ?? (string) ($release['id'] ?? uniqid()),
            'enclosures' => $enclosures,
            'categories' => [$release['tag_name'] ?? ''],
        ];
    }

    private function processMarkdown(string $markdown, string $owner, string $repo): string
    {
        $markdown = $this->enrichMarkdownMentions($markdown, $owner, $repo);
        $html = markdownToHtml($markdown);
        return $this->processHtml($html, $owner, $repo);
    }

    private function enrichMarkdownMentions(string $markdown, string $owner, string $repo): string
    {
        $repoUrl = 'https://github.com/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/issues';

        $markdown = preg_replace(
            '/(?<!\w)@([a-zA-Z0-9](?:[a-zA-Z0-9]|-(?=[a-zA-Z0-9])){0,38})(?!\w)/',
            '[@$1](https://github.com/$1)',
            $markdown
        );

        $markdown = preg_replace(
            '/(?<!\w)#(\d+)(?!\w)/',
            '[#$1](' . $repoUrl . '/$1)',
            $markdown
        );

        return preg_replace('/:[a-zA-Z0-9_+\-]+:/', '', $markdown);
    }

    private function processHtml(string $html, string $owner, string $repo): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8"><div id="w">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $this->transformAlerts($xpath);
        $this->shortenAutoLinks($xpath, $owner, $repo);
        $this->applyListStyles($xpath);

        $wrapper = $dom->getElementById('w');
        if (!$wrapper) {
            return '';
        }
        $content = $dom->saveHTML($wrapper);
        $content = preg_replace('#^\s*<div[^>]*>#', '', $content);
        $content = preg_replace('#</div>\s*$#', '', $content);
        $content = strip_tags(trim($content), self::ALLOWED_TAGS);

        return sprintf('<div style="%s">%s</div>', self::CSS['wrapper'], $content);
    }

    private function transformAlerts(\DOMXPath $xpath): void
    {
        $types = array_keys(self::CSS['alerts']);
        $pattern = '/\[!(' . implode('|', $types) . ')\]\s*/i';

        foreach ($xpath->query('//blockquote') as $bq) {
            $found = null;
            foreach ($xpath->query('.//text()', $bq) as $node) {
                if (preg_match($pattern, $node->nodeValue, $m)) {
                    $found = strtoupper($m[1]);
                    break;
                }
            }
            if (!$found) {
                foreach ($xpath->query('.//strong', $bq) as $strong) {
                    $text = strtolower(trim($strong->textContent));
                    if (in_array($text, array_map('strtolower', $types), true)) {
                        $found = $text;
                        $strong->parentNode->removeChild($strong);
                        break;
                    }
                }
            }
            if (!$found) {
                continue;
            }

            foreach ($xpath->query('.//text()', $bq) as $node) {
                if (preg_match($pattern, $node->nodeValue)) {
                    $node->nodeValue = preg_replace($pattern, '', $node->nodeValue);
                }
            }

            $color = self::CSS['alerts'][strtoupper($found)];
            $existing = $bq->getAttribute('style');
            $style = trim(($existing ? $existing . ' ' : '') . sprintf(self::CSS['alert_border'], $color) . ' ' . self::CSS['alert_base']);
            $bq->setAttribute('style', $style);
        }
    }

    private function shortenAutoLinks(\DOMXPath $xpath, string $owner, string $repo): void
    {
        $oq = preg_quote($owner, '~');
        $rq = preg_quote($repo, '~');
        foreach ($xpath->query('//a[@href]') as $link) {
            $href = $link->getAttribute('href');
            if (trim($link->textContent) !== $href) {
                continue;
            }
            if (preg_match('~^https://github\.com/' . $oq . '/' . $rq . '/(?:issues|pull)/(\d+)(?:[/?#].*)?$~i', $href, $m)) {
                $link->nodeValue = '#' . $m[1];
            } elseif (preg_match('~^https://github\.com/([^/]+)/([^/]+)/(?:issues|pull)/(\d+)(?:[/?#].*)?$~i', $href, $m)) {
                $link->nodeValue = $m[1] . '/' . $m[2] . '#' . $m[3];
            } elseif (preg_match('~^https://github\.com/([a-zA-Z0-9](?:[a-zA-Z0-9]|-(?=[a-zA-Z0-9])){0,38})$~', $href, $m)) {
                $link->nodeValue = '@' . $m[1];
            }
        }
    }

    private function applyListStyles(\DOMXPath $xpath): void
    {
        foreach ($xpath->query('//ul | //ol') as $list) {
            $existing = $list->getAttribute('style');
            $new = $list->nodeName === 'ul' ? self::CSS['ul'] : self::CSS['ol'];
            $list->setAttribute('style', trim(($existing ? $existing . ' ' : '') . $new));
        }
    }
}