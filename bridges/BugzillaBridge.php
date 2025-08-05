<?php

class BugzillaBridge extends BridgeAbstract
{
    const NAME = 'Bugzilla Bridge';
    const URI = 'https://www.bugzilla.org/';
    const DESCRIPTION = 'Bridge for any Bugzilla instance';
    const MAINTAINER = 'Yaman Qalieh';
    const PARAMETERS = [
        'global' => [
            'instance' => [
                'name' => 'Instance URL',
                'required' => true,
                'exampleValue' => 'https://bugzilla.mozilla.org'
            ]
        ],
        'Bug comments' => [
            'id' => [
                'name' => 'Bug tracking ID',
                'type' => 'number',
                'required' => true,
                'title' => 'Insert bug tracking ID',
                'exampleValue' => 121241
            ],
            'limit' => [
                'name' => 'Number of comments to return',
                'type' => 'number',
                'required' => false,
                'title' => 'Specify number of comments to return',
                'defaultValue' => -1
            ],
            'skiptags' => [
                'name' => 'Skip offtopic comments',
                'type' => 'checkbox',
                'title' => 'Excludes comments tagged as advocacy, metoo, or offtopic from the feed'
            ]
        ]
    ];

    const SKIPPED_ACTIVITY = [
        'cc' => true,
        'comment_tag' => true
    ];

    const SKIPPED_TAGS = ['advocacy', 'metoo', 'offtopic'];

    private $instance;
    private $bugid;
    private $buguri;
    private $title;

    public function getName()
    {
        if (!is_null($this->title)) {
            return $this->title;
        }
        return parent::getName();
    }

    public function getURI()
    {
        return $this->buguri ?? parent::getURI();
    }

    public function collectData()
    {
        $this->instance = rtrim($this->getInput('instance'), '/');
        $this->bugid = $this->getInput('id');
        $this->buguri = $this->instance . '/show_bug.cgi?id=' . $this->bugid;

        $url = $this->instance . '/rest/bug/' . $this->bugid;
        $this->getTitle($url);
        $this->collectComments($url . '/comment');
        $this->collectUpdates($url . '/history');

        usort($this->items, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        if ($this->getInput('limit') > 0) {
            $this->items = array_slice($this->items, 0, $this->getInput('limit'));
        }
    }

    protected function getTitle($url)
    {
        // Only request the summary for a faster request
        $json = self::getJSON($url . '?include_fields=summary');
        $this->title = 'Bug ' . $this->bugid . ' - ' .
                     $json['bugs'][0]['summary'] . ' - ' .
                     // Remove https://
                     substr($this->instance, 8);
    }

    protected function collectComments($url)
    {
        $json = self::getJSON($url);

        // Array of comments is here
        if (!isset($json['bugs'][$this->bugid]['comments'])) {
            throwClientException('Cannot find REST endpoint');
        }

        foreach ($json['bugs'][$this->bugid]['comments'] as $comment) {
            $item = [];
            if (
                $this->getInput('skiptags') and
                array_intersect(self::SKIPPED_TAGS, $comment['tags'])
            ) {
                continue;
            }
            $item['categories'] = $comment['tags'];
            $item['uri'] = $this->buguri . '#c' . $comment['count'];
            $item['title'] = 'Comment ' . $comment['count'];
            $item['timestamp'] = $comment['creation_time'];
            $item['author'] = $this->getUser($comment['creator']);
            $item['content'] = $comment['text'];
            if (isset($comment['is_markdown']) and $comment['is_markdown']) {
                $item['content'] = markdownToHtml($item['content']);
            }
            if (!is_null($comment['attachment_id'])) {
                $item['enclosures'] = [$this->instance . '/attachment.cgi?id=' . $comment['attachment_id']];
            }
            $this->items[] = $item;
        }
    }

    protected function collectUpdates($url)
    {
        $json = self::getJSON($url);

        // Array of changesets which contain an array of changes
        if (!isset($json['bugs']['0']['history'])) {
            throwClientException('Cannot find REST endpoint');
        }

        foreach ($json['bugs']['0']['history'] as $changeset) {
            $author = $this->getUser($changeset['who']);
            $timestamp = $changeset['when'];
            foreach ($changeset['changes'] as $change) {
                // Skip updates to the cc list and comment tagging
                if (isset(self::SKIPPED_ACTIVITY[$change['field_name']])) {
                    continue;
                }

                $item = [];
                $item['uri'] = $this->buguri;
                $item['title'] = 'Updated';
                $item['timestamp'] = $timestamp;
                $item['author'] = $author;
                $item['content'] = ucfirst($change['field_name']) . ': ' .
                                 ($change['removed'] === '' ? '[nothing]' : $change['removed']) . ' -> ' .
                                 ($change['added'] === '' ? '[nothing]' : $change['added']);
                $this->items[] = $item;
            }
        }
    }

    protected function getUser($user)
    {
        // Check if the user endpoint is available
        if ($this->loadCacheValue($this->instance . 'userEndpointClosed')) {
            return $user;
        }

        $cache = $this->loadCacheValue($this->instance . $user);
        if ($cache) {
            return $cache;
        }

        $url = $this->instance . '/rest/user/' . $user . '?include_fields=real_name';
        try {
            $json = self::getJSON($url);
            if (isset($json['error']) and $json['error']) {
                throw new Exception();
            }
        } catch (Exception $e) {
            $this->saveCacheValue($this->instance . 'userEndpointClosed', true);
            return $user;
        }

        $username = $json['users']['0']['real_name'];

        if (empty($username)) {
            $username = $user;
        }
        $this->saveCacheValue($this->instance . $user, $username);
        return $username;
    }

    protected static function getJSON($url)
    {
        $headers = [
            'Accept: application/json',
        ];
        return json_decode(getContents($url, $headers), true);
    }
}
