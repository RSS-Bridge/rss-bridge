<?php

/**
 * Gitea is a community managed lightweight code hosting solution.
 * https://docs.gitea.io/en-us/
 */

class GiteaBridge extends BridgeAbstract
{
    const NAME = 'Gitea';
    const URI = 'https://gitea.io';
    const DESCRIPTION = 'Returns the latest issues, commits or releases';
    const MAINTAINER = 'gileri';
    const CACHE_TIMEOUT = 300; // 5 minutes

    const PARAMETERS = [
        'global' => [
            'host' => [
                'name' => 'Host',
                'exampleValue' => 'https://gitea.com',
                'required' => true,
                'title' => 'Host name with its protocol, without trailing slash',
            ],
            'user' => [
                'name' => 'Username',
                'exampleValue' => 'gitea',
                'required' => true,
                'title' => 'User name as it appears in the URL',
            ],
            'project' => [
                'name' => 'Project name',
                'exampleValue' => 'helm-chart',
                'required' => true,
                'title' => 'Project name as it appears in the URL',
            ],
        ],
        'Commits' => [
            'branch' => [
                'name' => 'Branch name',
                'defaultValue' => 'master',
                'required' => true,
                'title' => 'Branch name as it appears in the URL',
            ],
        ],
        'Issues' => [
            'include_description' => [
                'name' => 'Include issue description',
                'type' => 'checkbox',
                'title' => 'Activate to include the issue description',
            ],
        ],
        'Single issue' => [
            'issue' => [
                'name' => 'Issue number',
                'type' => 'number',
                'exampleValue' => 100,
                'required' => true,
                'title' => 'Issue number from the issues list',
            ],
        ],
        'Single pull request' => [
            'pull_request' => [
                'name' => 'Pull request number',
                'type' => 'number',
                'exampleValue' => 100,
                'required' => true,
                'title' => 'Pull request number from the issues list',
            ],
        ],
        'Pull requests' => [
            'include_description' => [
                'name' => 'Include pull request description',
                'type' => 'checkbox',
                'title' => 'Activate to include the pull request description',
            ],
        ],
        'Releases' => [],
        'Tags' => [],
    ];

    private $title = '';

    public function getIcon()
    {
        return 'https://gitea.io/images/gitea.png';
    }

    public function getName()
    {
        switch ($this->queriedContext) {
            case 'Commits':
            case 'Issues':
            case 'Pull requests':
            case 'Releases':
            case 'Tags':
                return $this->title . ' ' . $this->queriedContext;
            case 'Single issue':
                return 'Issue ' . $this->getInput('issue') . ': ' . $this->title;
            case 'Single pull request':
                return 'Pull request ' . $this->getInput('pull_request') . ': ' . $this->title;
            default:
                return parent::getName();
        }
    }

    public function getURI()
    {
        switch ($this->queriedContext) {
            case 'Commits':
                return $this->getInput('host')
                . '/' . $this->getInput('user')
                . '/' . $this->getInput('project')
                . '/commits/' . $this->getInput('branch');

            case 'Issues':
                return $this->getInput('host')
                . '/' . $this->getInput('user')
                . '/' . $this->getInput('project')
                . '/issues/';

            case 'Single issue':
                return $this->getInput('host')
                . '/' . $this->getInput('user')
                . '/' . $this->getInput('project')
                . '/issues/' . $this->getInput('issue');

            case 'Releases':
                return $this->getInput('host')
                . '/' . $this->getInput('user')
                . '/' . $this->getInput('project')
                . '/releases/';

            case 'Tags':
                return $this->getInput('host')
                . '/' . $this->getInput('user')
                . '/' . $this->getInput('project')
                . '/tags/';

            case 'Pull requests':
                return $this->getInput('host')
                . '/' . $this->getInput('user')
                . '/' . $this->getInput('project')
                . '/pulls/';

            case 'Single pull request':
                return $this->getInput('host')
                . '/' . $this->getInput('user')
                . '/' . $this->getInput('project')
                . '/pulls/' . $this->getInput('pull_request');

            default:
                return parent::getURI();
        }
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getURI());
        $html = defaultLinkTo($html, $this->getURI());

        $this->title = $html->find('[property="og:title"]', 0)->content;

        switch ($this->queriedContext) {
            case 'Commits':
                $this->collectCommitsData($html);
                break;
            case 'Issues':
                $this->collectIssuesData($html);
                break;
            case 'Pull requests':
                $this->collectPullRequestsData($html);
                break;
            case 'Single issue':
                $this->collectSingleIssueOrPrData($html);
                break;
            case 'Single pull request':
                $this->collectSingleIssueOrPrData($html);
                break;
            case 'Releases':
                $this->collectReleasesData($html);
                break;
            case 'Tags':
                $this->collectTagsData($html);
                break;
        }
    }

    protected function collectReleasesData($html)
    {
        $releases = $html->find('#release-list > li')
            or throwServerException('Unable to find releases');

        foreach ($releases as $release) {
            $this->items[] = [
                'author' => $release->find('.author', 0)->plaintext,
                'uri' => $release->find('a', 0)->href,
                'title' => 'Release ' . $release->find('h4', 0)->plaintext,
                'timestamp' => $release->find('.time-since', 0)->title,
            ];
        }
    }

    protected function collectTagsData($html)
    {
        $tags = $html->find('table#tags-table > tbody > tr')
            or throwServerException('Unable to find tags');

        foreach ($tags as $tag) {
            $this->items[] = [
                'uri' => $tag->find('a', 0)->href,
                'title' => 'Tag ' . $tag->find('.release-tag-name > a', 0)->plaintext,
            ];
        }
    }

    protected function collectCommitsData($html)
    {
        $commits = $html->find('#commits-table tbody tr')
            or throwServerException('Unable to find commits');

        foreach ($commits as $commit) {
            $this->items[] = [
                'uri' => $commit->find('a.sha', 0)->href,
                'title' => $commit->find('.message span', 0)->plaintext,
                'author' => $commit->find('.author', 0)->plaintext,
                'timestamp' => $commit->find('.time-since', 0)->title,
                'uid' => $commit->find('.sha', 0)->plaintext,
            ];
        }
    }

    protected function collectIssuesData($html)
    {
        $issues = $html->find('.issue.list li')
            or throwServerException('Unable to find issues');

        foreach ($issues as $issue) {
            $uri = $issue->find('a', 0)->href;

            $item = [
                'uri' => $uri,
                'title' => trim($issue->find('a.index', 0)->plaintext) . ' | ' . $issue->find('a.title', 0)->plaintext,
                'author' => $issue->find('.desc a', 1)->plaintext,
                'timestamp' => $issue->find('.time-since', 0)->title,
            ];

            if ($this->getInput('include_description')) {
                $issue_html = getSimpleHTMLDOMCached($uri, 3600);

                $issue_html = defaultLinkTo($issue_html, $uri);

                $item['content'] = $issue_html->find('.comment .markup', 0);
            }

            $this->items[] = $item;
        }
    }

    protected function collectSingleIssueOrPrData($html)
    {
        $comments = $html->find('.comment')
            or throwServerException('Unable to find comments');

        foreach ($comments as $comment) {
            if (
                strpos($comment->getAttribute('class'), 'form') !== false
                || strpos($comment->getAttribute('class'), 'merge') !== false
            ) {
                // Ignore comment form and merge information
                continue;
            }
            $commentLink = $comment->find('a[href*="#issue"]', 0);
            $item = [
                'author' => $comment->find('a.author', 0)->plaintext,
                'content' => $comment->find('.render-content', 0),
            ];
            if ($commentLink !== null) {
                // Regular comment
                $item['uri'] = $commentLink->href;
                $item['title'] = str_replace($commentLink->plaintext, '', $comment->find('span', 0)->plaintext);
                $item['timestamp'] = $comment->find('.time-since', 0)->title;
            } else {
                // Change request comment
                $item['uri'] = $this->getURI() . '#' . $comment->getAttribute('id');
                $item['title'] = $comment->find('.comment-header .text', 0)->plaintext;
            }
            $this->items[] = $item;
        }

        $this->items = array_reverse($this->items);
    }

    protected function collectPullRequestsData($html)
    {
        $issues = $html->find('.issue.list li')
            or throwServerException('Unable to find pull requests');

        foreach ($issues as $issue) {
            $uri = $issue->find('a', 0)->href;

            $item = [
                'uri' => $uri,
                'title' => trim($issue->find('a.index', 0)->plaintext) . ' | ' . $issue->find('a.title', 0)->plaintext,
                'author' => $issue->find('.desc a', 1)->plaintext,
                'timestamp' => $issue->find('.time-since', 0)->title,
            ];

            if ($this->getInput('include_description')) {
                $issue_html = getSimpleHTMLDOMCached($uri, 3600);

                $issue_html = defaultLinkTo($issue_html, $uri);

                $item['content'] = $issue_html->find('.comment .markup', 0);
            }

            $this->items[] = $item;
        }
    }
}
