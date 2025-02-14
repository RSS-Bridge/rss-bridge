<?php

class GithubIssueBridge extends BridgeAbstract
{
    const MAINTAINER = 'Pierre Mazière';
    const NAME = 'Github Issue';
    const URI = 'https://github.com/';
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
            'q' => [
                'name' => 'Search Query',
                'defaultValue' => 'is:issue is:open sort:updated-desc',
                'required' => true
            ]
        ],
        'Issue comments' => [
            'i' => [
                'name' => 'Issue number',
                'type' => 'number',
                'exampleValue' => '2099',
                'required' => true
            ]
        ]
    ];

    // Allows generalization with GithubPullRequestBridge
    const BRIDGE_OPTIONS = [0 => 'Project Issues', 1 => 'Issue comments'];
    const URL_PATH = 'issues';
    const SEARCH_QUERY_PATH = 'issues';

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
                $uri .= static::SEARCH_QUERY_PATH . '?q=' . urlencode($this->getInput('q'));
            }
            return $uri;
        }

        return parent::getURI();
    }

    private function buildGitHubIssueCommentUri($issue_number, $comment_id)
    {
        // https://github.com/<user>/<project>/issues/<issue-number>#<id>
        return static::URI
        . $this->getInput('u')
        . '/'
        . $this->getInput('p')
        . '/' . static::URL_PATH . '/'
        . $issue_number
        . '#'
        . $comment_id;
    }

    private function extractIssueEvent($issueNbr, $title, $comment)
    {
        $uri = $this->buildGitHubIssueCommentUri($issueNbr, $comment->id);

        $author = $comment->find('.author, .avatar', 0);
        if ($author) {
            $author = trim($author->href, '/');
        } else {
            $author = '';
        }

        $title .= ' / '
            . trim(str_replace(
                ['octicon','-'],
                [''],
                $comment->find('.octicon', 0)->getAttribute('class')
            ));

        $time = $comment->find('relative-time', 0);
        if ($time === null) {
            return;
        }

        foreach ($comment->find('.Details-content--hidden, .btn') as $el) {
            $el->innertext = '';
        }
        $content = $comment->plaintext;

        $item = [];
        $item['author'] = $author;
        $item['uri'] = $uri;
        $item['title'] = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        $item['timestamp'] = strtotime($time->getAttribute('datetime'));
        $item['content'] = $content;
        return $item;
    }

    private function extractIssueComment($issueNbr, $title, $comment)
    {
        $uri = $this->buildGitHubIssueCommentUri($issueNbr, $comment->id);

        $authorDom = $comment->find('.author', 0);
        $author = $authorDom->plaintext ?? null;

        $header = $comment->find('.timeline-comment-header > h3', 0);
        $title .= ' / ' . ($header ? $header->plaintext : 'Activity');

        $time = $comment->find('relative-time', 0);
        if ($time === null) {
            return;
        }

        $content = $comment->find('.comment-body', 0)->innertext;

        $item = [];
        $item['author'] = $author;
        $item['uri'] = $uri;
        $item['title'] = html_entity_decode($title, ENT_QUOTES, 'UTF-8');
        $item['timestamp'] = strtotime($time->getAttribute('datetime'));
        $item['content'] = $content;
        return $item;
    }

    private function extractIssueComments($issue)
    {
        $items = [];
        $title = $issue->find('.gh-header-title', 0)->plaintext;
        $issueNbr = trim(
            substr($issue->find('.gh-header-number', 0)->plaintext, 1)
        );

        $comments = $issue->find(
            '.comment, .TimelineItem-badge'
        );

        foreach ($comments as $comment) {
            if ($comment->hasClass('comment')) {
                $comment = $comment->parent;
                $item = $this->extractIssueComment($issueNbr, $title, $comment);
                if ($item !== null) {
                    $items[] = $item;
                }
                continue;
            } else {
                $comment = $comment->parent;
                $item = $this->extractIssueEvent($issueNbr, $title, $comment);
                if ($item !== null) {
                    $items[] = $item;
                }
            }
        }
        return $items;
    }

    public function collectData()
    {
        $url = $this->getURI();
        $html = getSimpleHTMLDOM($url);

        switch ($this->queriedContext) {
            case static::BRIDGE_OPTIONS[1]: // Issue comments
                $this->items = $this->extractIssueComments($html);
                break;
            case static::BRIDGE_OPTIONS[0]: // Project Issues
                $issues = $html->find('.js-active-navigation-container .js-navigation-item');
                $issues = $html->find('.IssueRow-module__row--XmR1f');
                foreach ($issues as $issue) {
                    $info = $issue->find('.issue-item-module__authorCreatedLink--wFZvk', 0);

                    preg_match('/\/([0-9]+)$/', $issue->find('a', 0)->href, $match);
                    $issueNbr = $match[1];

                    $item = [];
                    $item['content'] = '';

                    if ($this->getInput('c')) {
                        $uri = static::URI . $this->getInput('u')
                         . '/' . $this->getInput('p') . '/' . static::URL_PATH . '/' . $issueNbr;
                        $issue = getSimpleHTMLDOMCached($uri, static::CACHE_TIMEOUT);
                        if ($issue) {
                            $this->items = array_merge(
                                $this->items,
                                $this->extractIssueComments($issue)
                            );
                            continue;
                        }
                        $item['content'] = 'Can not extract comments from ' . $uri;
                    }

                    $item['author'] = $issue->find('a', 1)->plaintext;
                    $item['timestamp'] = strtotime(
                        $issue->find('relative-time', 0)->getAttribute('datetime')
                    );
                    $item['title'] = html_entity_decode(
                        $issue->find('h3', 0)->plaintext,
                        ENT_QUOTES,
                        'UTF-8'
                    );

                    //$comment_count = 0;
                    //if ($span = $issue->find('a[aria-label*="comment"] span', 0)) {
                    //    $comment_count = $span->plaintext;
                    //}

                    //$item['content'] .= "\n" . 'Comments: ' . $comment_count;
                    $item['uri'] = self::URI
                             . trim($issue->find('a', 0)->getAttribute('href'), '/');
                    $this->items[] = $item;
                }
                break;
        }

        array_walk($this->items, function (&$item) {
            $item['content'] = preg_replace('/\s+/', ' ', $item['content']);
            $item['content'] = str_replace(
                'href="/',
                'href="' . static::URI,
                $item['content']
            );
            $item['content'] = str_replace(
                'href="#',
                'href="' . substr($item['uri'], 0, strpos($item['uri'], '#') + 1),
                $item['content']
            );
            $item['title'] = preg_replace('/\s+/', ' ', $item['title']);
        });
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
