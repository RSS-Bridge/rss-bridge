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
        'hide_assets' => [
            'name' => 'Hide assets',
            'type' => 'checkbox',
            'title' => 'Hide attached assets (binaries/files) from the feed entries'
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
        $hideAssets = $this->getInput('hide_assets');

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
                $content = $this->processContent($release['body'], $owner, $repo);
            }

            $enclosures = [];
            if (!$hideAssets && !empty($release['assets'])) {
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

    // Content processing
    private function processContent(string $body, string $owner, string $repo): string
    {
        $content = markdownToHtml($body);
        $content = $this->processTextNodes($content, $owner, $repo);
        $content = $this->applyVisualEnhancements($content);
        return $content;
    }

    // Beautifier
    private function processTextNodes(string $html, string $owner, string $repo): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            . '<div id="rssbridge-wrapper">' . $html . '</div>'
        );
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        // Text non-formatted
        $textNodes = $xpath->query(
            '//text()[not(ancestor::a) and not(ancestor::code) and not(ancestor::pre) and not(ancestor::script)]'
        );

        foreach ($textNodes as $node) {
            $text = $node->nodeValue;
            $newText = $text;

            // @username > link
            $newText = preg_replace(
                '/(?<!\w)@([a-zA-Z0-9](?:[a-zA-Z0-9]|-(?=[a-zA-Z0-9])){0,38})(?!\w)/',
                '<a href="https://github.com/$1">@$1</a>',
                $newText
            );

            // #issue > link
            $newText = preg_replace(
                '/(?<!\w)#(\d+)(?!\w)/',
                '<a href="https://github.com/' . rawurlencode($owner) . '/' . rawurlencode($repo) . '/issues/$1">#$1</a>',
                $newText
            );

            // :emoji: > wipe
            $newText = preg_replace('/:[a-zA-Z0-9_+\-]+:/', '', $newText);

            if ($newText !== $text) {
                $this->replaceNodeWithHtml($dom, $node, $newText);
            }
        }

        $this->shortenAutoLinks($dom, $owner, $repo);
        $wrapper = $dom->getElementById('rssbridge-wrapper');
        $content = '';
        if ($wrapper) {
            foreach ($wrapper->childNodes as $child) {
                $content .= $dom->saveHTML($child);
            }
        }

        return $content;
    }

    // Fix plain text
    private function replaceNodeWithHtml(\DOMDocument $dom, \DOMNode $node, string $html): void
    {
        $tempDom = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $tempDom->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">'
            . '<div>' . $html . '</div>'
        );
        libxml_clear_errors();

        $tempDiv = $tempDom->getElementsByTagName('div')->item(0);
        if ($tempDiv) {
            $fragment = $dom->createDocumentFragment();
            foreach ($tempDiv->childNodes as $child) {
                $importedNode = $dom->importNode($child, true);
                $fragment->appendChild($importedNode);
            }
            $node->parentNode->replaceChild($fragment, $node);
        }
    }

    // Fix URLs
    private function shortenAutoLinks(\DOMDocument $dom, string $owner, string $repo): void
    {
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//a[@href]');
        
        $ownerQuoted = preg_quote($owner, '#');
        $repoQuoted = preg_quote($repo, '#');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $text = trim($link->textContent);

            if ($text === $href) {
                if (preg_match('#^https://github\.com/' . $ownerQuoted . '/' . $repoQuoted . '/(?:issues|pull)/(\d+)(?:[/?#].*)?$#i', $href, $m)) {
                    $link->nodeValue = '#' . $m[1];
                } elseif (preg_match('#^https://github\.com/([^/]+)/([^/]+)/(?:issues|pull)/(\d+)(?:[/?#].*)?$#i', $href, $m)) {
                    $link->nodeValue = $m[1] . '/' . $m[2] . '#' . $m[3];
                } elseif (preg_match('#^https://github\.com/([a-zA-Z0-9](?:[a-zA-Z0-9]|-(?=[a-zA-Z0-9])){0,38})$#', $href, $m)) {
                    $link->nodeValue = '@' . $m[1];
                }
            }
        }
    }

    // Visuals
    private function applyVisualEnhancements(string $html): string
    {
        // Headers
        $html = preg_replace(
            '/<h([1-6])[^>]*>(.*?)<\/h\1>/is',
            '<p><strong>$2</strong></p>',
            $html
        );

        // Images
        $html = preg_replace_callback(
            '/<img([^>]*)>/i',
            function ($matches) {
                $attrs = $matches[1];
                if (stripos($attrs, 'max-width') === false) {
                    if (preg_match('/style\s*=\s*(["\'])(.*?)\1/i', $attrs)) {
                        $attrs = preg_replace(
                            '/style\s*=\s*(["\']).*?\1/i',
                            'style="$2; max-width:100%; height:auto;"',
                            $attrs
                        );
                    } else {
                        $attrs .= ' style="max-width:100%; height:auto;"';
                    }
                }
                return '<img' . $attrs . '>';
            },
            $html
        );

        // Lists
        $html = preg_replace_callback(
            '/<(ul|ol)([^>]*)>/i',
            function ($matches) {
                $tag = strtolower($matches[1]);
                $attrs = $matches[2];
                $listStyle = $tag === 'ul' ? 'disc' : 'decimal';

                if (preg_match('/style\s*=\s*(["\'])(.*?)\1/i', $attrs, $styleMatch)) {
                    $existingStyle = $styleMatch[2];
                    $newStyle = rtrim($existingStyle, '; ');
                    if (stripos($existingStyle, 'list-style') === false) {
                        $newStyle .= '; list-style-type:' . $listStyle;
                    }
                    if (stripos($existingStyle, 'padding-left') === false) {
                        $newStyle .= '; padding-left:24px';
                    }
                    $attrs = preg_replace(
                        '/style\s*=\s*(["\']).*?\1/i',
                        'style="' . $newStyle . '"',
                        $attrs
                    );
                } else {
                    $attrs .= ' style="list-style-type:' . $listStyle . '; padding-left:24px; margin:8px 0;"';
                }

                return '<' . $tag . $attrs . '>';
            },
            $html
        );

        // Styles
        return '<div style="font-size:14px; line-height:1.6; word-wrap:break-word;">'
            . '<style>'
            . 'code { background-color:#f0f0f0; padding:2px 5px; border-radius:3px; font-size:13px; }'
            . 'pre { background-color:#f0f0f0; padding:12px; border-radius:4px; overflow-x:auto; }'
            . 'pre code { background:none; padding:0; }'
            . 'ul { list-style-type:disc; padding-left:24px; margin:8px 0; }'
            . 'ol { list-style-type:decimal; padding-left:24px; margin:8px 0; }'
            . 'li { margin:4px 0; }'
            . 'blockquote { border-left:3px solid #ddd; padding-left:12px; margin-left:0; color:#555; }'
            . 'a { color:#0366d6; text-decoration:none; }'
            . 'a:hover { text-decoration:underline; }'
            . '</style>'
            . $html
            . '</div>';
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