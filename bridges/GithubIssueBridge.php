<?php

class GithubIssueBridge extends BridgeAbstract
{
    const MAINTAINER = 'Pierre Mazière';
    const NAME = 'Github Issue';
    const URI = 'https://github.com/';
    const API_URI = 'https://api.github.com/';
    const CACHE_TIMEOUT = 600; // 10m
    const DESCRIPTION = 'Returns the issues or comments of an issue of a github project';

    const PARAMETERS = [
        'global' => [
            'u' => [
                'name' => 'User name',
                'exampleValue' => 'RSS-Bridge',
                'required' => true
            ],
            'p' => [
                'name' => 'Project name',
                'exampleValue' => 'rss-bridge',
                'required' => true
            ]
        ],
        'Project Issues' => [
            'c' => [
                'name' => 'Show Issues Comments',
                'type' => 'checkbox'
            ],
            'e' => [
                'name' => 'Show Events',
                'type' => 'checkbox'
            ],
            'q' => [
                'name' => 'Search Query',
                'defaultValue' => 'is:issue is:open sort:updated-desc',
                'required' => true
            ]
        ],
        'Issue comments' => [
			'e' => [
                'name' => 'Show Events',
                'type' => 'checkbox'
            ],
            'i' => [
                'name' => 'Issue number',
                'type' => 'number',
                'exampleValue' => '2099',
                'required' => true
            ],
        ]
    ];

    // Allows generalization with GithubPullRequestBridge
    const BRIDGE_OPTIONS = [0 => 'Project Issues', 1 => 'Issue comments'];
    const URL_PATH = 'issues';
    // Used to restrict the GitHub search api to issues vs pulls (overridden by GithubPullRequestBridge)
    const SEARCH_TYPE_QUALIFIER = 'is:issue';

    public function getName()
    {
        $name = $this->getInput('u') . '/' . $this->getInput('p');
        switch ($this->queriedContext) {
            case static::BRIDGE_OPTIONS[0]: // Project Issues
                $prefix = static::NAME . 's for ';
                if ($this->getInput('c')) {
                    $prefix = static::NAME . 's comments for ';
                }
                $name = $prefix . $name;
                break;
            case static::BRIDGE_OPTIONS[1]: // Issue comments
                $name = static::NAME . ' ' . $name . ' #' . $this->getInput('i');
                break;
            default:
                return parent::getName();
        }
        return $name;
    }

    public function getURI()
    {
        if (null !== $this->getInput('u') && null !== $this->getInput('p')) {
            $uri = static::URI . $this->getInput('u') . '/'
                 . $this->getInput('p') . '/';
            if ($this->queriedContext === static::BRIDGE_OPTIONS[1]) {
                $uri .= static::URL_PATH . '/' . $this->getInput('i');
            } else {
                $uri .= static::URL_PATH;
            }
            return $uri;
        }

        return parent::getURI();
    }

    private function apiHeaders()
    {
        return [
            'Accept: application/vnd.github+json',
            'User-Agent: RSS-Bridge',
            'X-GitHub-Api-Version: 2022-11-28',
        ];
    }

    private function apiRequest($url)
    {
        $json = getContents($url, $this->apiHeaders());
        $data = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            returnServerError('Unable to parse GitHub API response for ' . $url);
        }
        if (isset($data['message']) && !isset($data['html_url']) && array_key_exists('documentation_url', $data)) {
            // Looks like an API error payload, e.g. rate limiting or not found
            returnServerError('GitHub API error for ' . $url . ': ' . $data['message']);
        }
        return $data;
    }

    private function buildGitHubIssueUri($issue_number)
    {
        return static::URI
            . $this->getInput('u') . '/' . $this->getInput('p')
            . '/' . static::URL_PATH . '/' . $issue_number;
    }

    private function buildGitHubIssueCommentUri($issue_number, $comment_id)
    {
        return $this->buildGitHubIssueUri($issue_number) . '#issuecomment-' . $comment_id;
    }

    private function markdownToHtml($text)
    {
        if ($text === null || $text === '') {
            return '';
        }
        // GitHub's API returns raw markdown in the body. We don't have a markdown
        // renderer available, so at minimum make it safe and readable as plain text.
        return nl2br(htmlspecialchars($text, ENT_QUOTES, 'UTF-8'));
    }

    /**
     * Parses a GitHub search-style query string (e.g. "is:issue is:open sort:updated-desc")
     * into a q= value usable by the GitHub Search API plus separate sort/order params.
     */
    private function parseSearchQuery($query)
    {
        $sort = null;
        $order = null;

        $query = preg_replace_callback(
            '/\bsort:([a-zA-Z\-]+)\b/',
            function ($m) use (&$sort, &$order) {
                $value = $m[1];
                if (str_ends_with($value, '-desc')) {
                    $sort = substr($value, 0, -5);
                    $order = 'desc';
                } elseif (str_ends_with($value, '-asc')) {
                    $sort = substr($value, 0, -4);
                    $order = 'asc';
                } else {
                    $sort = $value;
                }
                return '';
            },
            $query
        );

        return [
            'q' => trim(preg_replace('/\s+/', ' ', $query)),
            'sort' => $sort,
            'order' => $order,
        ];
    }

    /**
     * Builds a short, single-line snippet of a markdown body, suitable for use in a title.
     * Strips markdown blockquote lines (lines starting with '>') first, since these are
     * usually quoting someone else's earlier comment rather than the commenter's own words.
     */
    private function makeSnippet($text, $maxLength = 80)
    {
        if ($text === null || $text === '') {
            return '';
        }

        $lines = preg_split('/\r\n|\r|\n/', $text);
        $lines = array_filter($lines, function ($line) {
            return !preg_match('/^\s*>/', $line);
        });
        $text = implode(' ', $lines);

        $snippet = preg_replace('/\s+/', ' ', trim($text));
        if ($snippet === '') {
            return '';
        }
        if (mb_strlen($snippet) > $maxLength) {
            $snippet = mb_substr($snippet, 0, $maxLength) . '…';
        }
        return $snippet;
    }

    private function buildIssueItem($issue)
    {
        $item = [];
        $item['uri'] = $issue['html_url'];
        $item['title'] = $issue['title'];
        $item['author'] = $issue['user']['login'] ?? '';
        // Use creation time here: this represents the issue/PR as first filed.
        // (updated_at reflects the *last* change to the issue, e.g. a recent
        // comment or label edit, and would otherwise sort newly-opened issues
        // as if they were old, or vice versa.)
        $item['timestamp'] = strtotime($issue['created_at']);
        $labels = array_map(function ($label) {
            return is_array($label) ? ($label['name'] ?? '') : $label;
        }, $issue['labels'] ?? []);
        $content = $this->markdownToHtml($issue['body'] ?? '');
        if ($labels) {
            $content = '<p><strong>Labels:</strong> ' . implode(', ', $labels) . '</p>' . $content;
        }
        $item['content'] = $content;
        $item['uid'] = (string)$issue['id'];
        return $item;
    }

    private function buildCommentItem($issueNbr, $title, $comment)
    {
        $item = [];
        $item['uri'] = $this->buildGitHubIssueCommentUri($issueNbr, $comment['id']);
        $snippet = $this->makeSnippet($comment['body'] ?? '');
        $item['title'] = $snippet !== '' ? $snippet : ($title . ' / Comment');
        $item['author'] = $comment['user']['login'] ?? '';
        // Comments are immutable for our purposes here: created_at is the right
        // anchor (an edited comment shouldn't jump the feed to "now").
        $item['timestamp'] = strtotime($comment['created_at']);
        $item['content'] = $this->markdownToHtml($comment['body'] ?? '');
        $item['uid'] = (string)$comment['id'];
        return $item;
    }

    private function buildGitHubEventUri($issue_number, $event)
    {
        if (!empty($event['id'])) {
            return $this->buildGitHubIssueUri($issue_number) . '#event-' . $event['id'];
        }
        return $this->buildGitHubIssueUri($issue_number);
    }

    /**
     * Turns a single GitHub issue-timeline event into a human-readable title.
     * Returns null for event types we don't render (e.g. 'commented', which is
     * already covered by the comments endpoint, or anything unrecognized).
     */
    private function describeEvent($event)
    {
        $type = $event['event'] ?? '';
        switch ($type) {
            case 'closed':
                return 'Closed';
            case 'reopened':
                return 'Reopened';
            case 'labeled':
                return 'Label added: ' . ($event['label']['name'] ?? '?');
            case 'unlabeled':
                return 'Label removed: ' . ($event['label']['name'] ?? '?');
            case 'renamed':
                $from = $event['rename']['from'] ?? '?';
                $to = $event['rename']['to'] ?? '?';
                return 'Renamed: "' . $from . '" → "' . $to . '"';
            case 'assigned':
                return 'Assigned to ' . ($event['assignee']['login'] ?? '?');
            case 'unassigned':
                return 'Unassigned from ' . ($event['assignee']['login'] ?? '?');
            case 'milestoned':
                return 'Milestone added: ' . ($event['milestone']['title'] ?? '?');
            case 'demilestoned':
                return 'Milestone removed: ' . ($event['milestone']['title'] ?? '?');
            case 'locked':
                return 'Locked';
            case 'unlocked':
                return 'Unlocked';
            case 'pinned':
                return 'Pinned';
            case 'unpinned':
                return 'Unpinned';
            case 'transferred':
                return 'Transferred';
            case 'merged':
                return 'Merged';
            case 'review_requested':
                $reviewer = $event['requested_reviewer']['login'] ?? ($event['requested_team']['name'] ?? '?');
                return 'Review requested from ' . $reviewer;
            case 'review_request_removed':
                $reviewer = $event['requested_reviewer']['login'] ?? ($event['requested_team']['name'] ?? '?');
                return 'Review request removed for ' . $reviewer;
            case 'reviewed':
                return 'Reviewed (' . ($event['state'] ?? '?') . ')';
            case 'cross-referenced':
                $source = $event['source']['issue']['html_url'] ?? null;
                $number = $event['source']['issue']['number'] ?? '?';
                return 'Mentioned in #' . $number;
            case 'referenced':
                return 'Referenced in a commit';
            case 'connected':
                return 'Linked to this issue';
            case 'disconnected':
                return 'Unlinked from this issue';
            case 'convert_to_draft':
                return 'Converted to draft';
            case 'ready_for_review':
                return 'Marked ready for review';
            case 'head_ref_force_pushed':
                return 'Branch force-pushed';
            case 'head_ref_deleted':
                return 'Branch deleted';
            case 'head_ref_restored':
                return 'Branch restored';
            default:
                return null;
        }
    }

    private function buildEventItem($issueNbr, $event)
    {
        $description = $this->describeEvent($event);
        if ($description === null) {
            return null;
        }

        // cross-referenced events still belong on this issue's own page; the
        // actor who made the mention lives on the *source* issue/PR though.
        $uri = $this->buildGitHubEventUri($issueNbr, $event);
        if (($event['event'] ?? '') === 'cross-referenced') {
            $author = $event['source']['issue']['user']['login'] ?? '';
        } else {
            $author = $event['actor']['login'] ?? '';
        }

        $timestamp = $event['created_at'] ?? null;
        if ($timestamp === null) {
            return null;
        }

        $item = [];
        $item['uri'] = $uri;
        $item['title'] = $description;
        $item['author'] = $author;
        $item['timestamp'] = strtotime($timestamp);
        $item['content'] = $description;
        $item['uid'] = (string)($event['id'] ?? $event['node_id'] ?? md5(implode('|', [
			$issueNbr,
			$event['event'] ?? '',
			$timestamp,
			$event['source']['issue']['id'] ?? $event['actor']['login'] ?? '',
		])));
        return $item;
    }

    private function collectIssueEvents($issueNbr)
    {
        $items = [];
        $timelineUrl = static::API_URI . 'repos/' . $this->getInput('u') . '/' . $this->getInput('p')
            . '/issues/' . $issueNbr . '/timeline?per_page=100';
        $headers = array_merge($this->apiHeaders(), [
            // The Timeline API historically required this media type; harmless to
            // include even where it's no longer strictly necessary.
            'Accept: application/vnd.github.mockingbird-preview+json',
        ]);

        $page = 1;
        do {
            $json = getContents($timelineUrl . '&page=' . $page, $headers);
            $events = json_decode($json, true);
            if (!is_array($events) || count($events) === 0) {
                break;
            }
            foreach ($events as $event) {
                // 'commented' events are already covered by collectIssueComments();
                // skip to avoid duplicate items.
                if (($event['event'] ?? '') === 'commented') {
                    continue;
                }
                $item = $this->buildEventItem($issueNbr, $event);
                if ($item !== null) {
                    $items[] = $item;
                }
            }
            $page++;
        } while (count($events) === 100);

        return $items;
    }
    private function collectIssueComments($issueNbr)
    {
        $items = [];
        $issueUrl = static::API_URI . 'repos/' . $this->getInput('u') . '/' . $this->getInput('p')
            . '/issues/' . $issueNbr;
        $issue = $this->apiRequest($issueUrl);

        $title = $issue['title'] ?? ('#' . $issueNbr);

        // The issue body itself is treated as the first item (mirrors old behaviour
        // of including the opening post in the timeline)
        $opening = $this->buildIssueItem($issue);
        $openingSnippet = $this->makeSnippet($issue['body'] ?? '');
        $opening['title'] = $title . ' / Opened' . ($openingSnippet !== '' ? ': ' . $openingSnippet : '');
        $items[] = $opening;

        $commentsUrl = $issueUrl . '/comments?per_page=100';
        $page = 1;
        do {
            $pagedUrl = $commentsUrl . '&page=' . $page;
            $comments = $this->apiRequest($pagedUrl);
            if (!is_array($comments) || count($comments) === 0) {
                break;
            }
            foreach ($comments as $comment) {
                $items[] = $this->buildCommentItem($issueNbr, $title, $comment);
            }
            $page++;
        } while (count($comments) === 100);

        if ($this->getInput('e')) {
            $items = array_merge($items, $this->collectIssueEvents($issueNbr));
        }

        // Feeds are conventionally newest-first; readers that don't re-sort
        // client-side (e.g. a raw ?format=Html view) would otherwise show
        // the oldest post/comment/event at the top.
        usort($items, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        return $items;
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            case static::BRIDGE_OPTIONS[1]: // Issue comments
                $this->items = $this->collectIssueComments($this->getInput('i'));
                break;

            case static::BRIDGE_OPTIONS[0]: // Project Issues
                $parsed = $this->parseSearchQuery($this->getInput('q'));
                $repoQualifier = 'repo:' . $this->getInput('u') . '/' . $this->getInput('p');
                $q = trim($parsed['q'] . ' ' . static::SEARCH_TYPE_QUALIFIER . ' ' . $repoQualifier);

                $params = ['q' => $q, 'per_page' => '50'];
                if ($parsed['sort']) {
                    $params['sort'] = $parsed['sort'];
                }
                if ($parsed['order']) {
                    $params['order'] = $parsed['order'];
                }

                $searchUrl = static::API_URI . 'search/issues?' . http_build_query($params);
                $result = $this->apiRequest($searchUrl);
                $issues = $result['items'] ?? [];

                foreach ($issues as $issue) {
                    if ($this->getInput('c')) {
                        $issueNbr = $issue['number'];
                        $this->items = array_merge(
                            $this->items,
                            $this->collectIssueComments($issueNbr)
                        );
                        continue;
                    }
                    $this->items[] = $this->buildIssueItem($issue);
                }
                break;
        }
    }

    public function detectParameters($url)
    {
        if (
            filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) === false
            || strpos($url, self::URI) !== 0
        ) {
            return null;
        }

        $url_components = parse_url($url);
        $path_segments = array_values(array_filter(explode('/', $url_components['path'])));

        switch (count($path_segments)) {
            case 2: // Project issues
                [$user, $project] = $path_segments;
                $show_comments = 'off';
                $context = 'Project Issues';
                break;
            case 3: // Project issues with issue comments
                if ($path_segments[2] !== static::URL_PATH) {
                    return null;
                }
                [$user, $project] = $path_segments;
                $show_comments = 'on';
                $context = 'Project Issues';
                break;
            case 4: // Issue comments
                [$user, $project, /* issues */, $issue] = $path_segments;
                $context = 'Issue comments';
                break;
            default:
                return null;
        }

        return [
            'context' => $context,
            'u' => $user,
            'p' => $project,
            'c' => $show_comments ?? null,
            'i' => $issue ?? null,
        ];
    }
}
