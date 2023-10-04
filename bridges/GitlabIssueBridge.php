<?php

class GitlabIssueBridge extends BridgeAbstract
{
    const MAINTAINER = 'Mynacol';
    const NAME = 'Gitlab Issue/Merge Request/Epic';
    const URI = 'https://gitlab.com/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns  comments of an issue/MR/Epic of a gitlab project';

    const PARAMETERS = [
        'global' => [
            'h' => [
                'name' => 'Gitlab instance host name',
                'exampleValue' => 'gitlab.com',
                'defaultValue' => 'gitlab.com',
                'required' => true
            ],
            'u' => [
                'name' => 'User/Organization name',
                'exampleValue' => 'gitlab-org',
                'required' => true
            ],
            'p' => [
                'name' => 'Project name',
                'exampleValue' => 'gitlab-foss',
                'required' => true
            ]

        ],
        'Issue comments' => [
            'i' => [
                'name' => 'Issue number',
                'type' => 'number',
                'exampleValue' => '1',
                'required' => true
            ]
        ],
        'Merge Request comments' => [
            'i' => [
                'name' => 'Merge Request number',
                'type' => 'number',
                'exampleValue' => '1',
                'required' => true
            ]
        ],
        'Epic comments' => [
            'i' => [
                'name' => 'Epic number',
                'type' => 'number',
                'exampleValue' => '1',
                'required' => true
            ]
        ]
    ];

    public function getName()
    {
        $name = $this->getInput('h') . '/' . $this->getInput('u') . '/' . $this->getInput('p');
        switch ($this->queriedContext) {
            case 'Issue comments':
                $name .= ' Issue #' . $this->getInput('i');
                break;
            case 'Merge Request comments':
                $name .= ' MR !' . $this->getInput('i');
                break;
            case 'Epic comments':
                $name .= ' Epic &' . $this->getInput('i');
                break;
            default:
                return parent::getName();
        }
        return $name;
    }

    public function getURI()
    {
        $host = $this->getInput('h') ?? 'gitlab.com';
        $uri = 'https://' . $host . '/' . $this->getInput('u') . '/'
             . $this->getInput('p') . '/';
        switch ($this->queriedContext) {
            case 'Issue comments':
                $uri .= '-/issues';
                break;
            case 'Merge Request comments':
                $uri .= '-/merge_requests';
                break;
            case 'Epic comments':
                $uri = 'https://' . $host . '/groups/' . $this->getInput('u') . '/-/epics';
                break;
            default:
                return $uri;
        }
        $uri .= '/' . $this->getInput('i');
        return $uri;
    }

    public function getIcon()
    {
        return 'https://' . $this->getInput('h') . '/favicon.ico';
    }

    public function collectData()
    {
        switch ($this->queriedContext) {
            case 'Issue comments':
                $this->items[] = $this->parseIssueDescription();
                break;
            case 'Merge Request comments':
                $this->items[] = $this->parseMergeRequestDescription();
                break;
            default:
                break;
        }

        /* parse issue/MR comments */
        $comments_uri = $this->getURI() . '/discussions.json';
        $comments = getContents($comments_uri);
        $comments = json_decode($comments, false);

        foreach ($comments as $value) {
            foreach ($value->notes as $comment) {
                $item = [];
                if ($comment->noteable_note_url !== null) {
                    $item['uri'] = $comment->noteable_note_url;
                    $item['uid'] = $item['uri'];
                }

                // TODO fix invalid timestamps (fdroid bot)
                $item['timestamp'] = $comment->created_at ?? $comment->updated_at ?? $comment->last_edited_at;
                $author = $comment->author ?? $comment->last_edited_by;
                $item['author'] = '<img src="' . $author->avatar_url . '" width=24></img> <a href="https://' .
                    $this->getInput('h') . $author->path . '">' . $author->name . ' @' . $author->username . '</a>';

                $content = '';
                if ($comment->system) {
                    $content = $comment->note_html;
                    if ($comment->type === 'StateNote') {
                        $content .= ' the issue';
                    } elseif ($comment->type === null) {
                        // e.g. "added 900 commits\n800 from master\n175h4d - commit message\n..."
                        $content = str_get_html($comment->note_html)->find('p', 0);
                    }
                } else {
                    // no switch-case to do strict comparison
                    if ($comment->type === null || $comment->type === 'DiscussionNote') {
                        $content = 'commented';
                    } elseif ($comment->type === 'DiffNote') {
                        $content = 'commented on a thread';
                    } else {
                        $content = $comment->note_html;
                    }
                }
                $item['title'] = $author->name . " $content";

                $content = $this->fixImgSrc($comment->note_html);
                $item['content'] = defaultLinkTo($content, 'https://' . $this->getInput('h') . '/');

                $this->items[] = $item;
            }
        }
    }

    private function parseIssueDescription()
    {
        $description_uri = $this->getURI() . '.json';
        $description = getContents($description_uri);
        $description = json_decode($description, false);
        $description_html = getSimpleHtmlDomCached($this->getURI());

        $item = [];
        $item['uri'] = $this->getURI();
        $item['uid'] = $item['uri'];

        $item['timestamp'] = $description->created_at ?? $description->updated_at;

        $author = $this->parseAuthor($description_html);
        if ($author) {
            $item['author'] = $author;
        }

        $item['title'] = $description->title;
        $item['content'] = markdownToHtml($description->description);

        return $item;
    }

    private function parseMergeRequestDescription()
    {
        $description_uri = $this->getURI() . '/cached_widget.json';
        $description = getContents($description_uri);
        $description = json_decode($description, false);
        $description_html = getSimpleHtmlDomCached($this->getURI());

        $item = [];
        $item['uri'] = $this->getURI();
        $item['uid'] = $item['uri'];

        $item['timestamp'] = $description_html->find('.merge-request-details time', 0)->datetime;

        $author = $this->parseAuthor($description_html);
        if ($author) {
            $item['author'] = $author;
        }

        $item['title'] = 'Merge Request ' . $description->title;
        $item['content'] = markdownToHtml($description->description);

        return $item;
    }

    private function fixImgSrc($html)
    {
        if (is_string($html)) {
            $html = str_get_html($html);
        }

        foreach ($html->find('img') as $img) {
            $img->src = $img->getAttribute('data-src');
        }
        return $html;
    }

    private function parseAuthor($description_html)
    {
        $description_html = $this->fixImgSrc($description_html);

        $authors = $description_html->find('.issuable-meta a.author-link, .merge-request a.author-link');
        $editors = $description_html->find('.edited-text a.author-link');
        if ($authors === [] && $editors === []) {
            return null;
        }
        $author_str = implode(' ', $authors);
        if ($editors) {
            $author_str .= ', ' . implode(' ', $editors);
        }
        return defaultLinkTo($author_str, 'https://' . $this->getInput('h') . '/');
    }
}
