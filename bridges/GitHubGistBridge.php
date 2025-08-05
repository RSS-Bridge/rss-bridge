<?php

class GitHubGistBridge extends BridgeAbstract
{
    const NAME = 'GitHubGist comment bridge';
    const URI = 'https://gist.github.com';
    const DESCRIPTION = 'Generates feeds for Gist comments';
    const MAINTAINER = 'logmanoriginal';
    const CACHE_TIMEOUT = 3600;

    const PARAMETERS = [[
        'id' => [
            'name' => 'Gist',
            'type' => 'text',
            'required' => true,
            'title' => 'Insert Gist ID or URI',
            'exampleValue' => '2646763'
        ]
    ]];

    private $filename;

    public function getURI()
    {
        $id = $this->getInput('id') ?: '';

        $urlpath = parse_url($id, PHP_URL_PATH);

        if ($urlpath) {
            $components = explode('/', $urlpath);
            $id = end($components);
        }

        return static::URI . '/' . $id;
    }

    public function getName()
    {
        return $this->filename ? $this->filename . ' - ' . static::NAME : static::NAME;
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM(
            $this->getURI(),
            null,
            null,
            true,
            true,
            DEFAULT_TARGET_CHARSET,
            false, // Do NOT remove line breaks
            DEFAULT_BR_TEXT,
            DEFAULT_SPAN_TEXT
        );

        $html = defaultLinkTo($html, $this->getURI());

        $fileinfo = $html->find('[class~="file-info"]', 0)
            or throwServerException('Could not find file info!');

        $this->filename = $fileinfo->plaintext;

        $comments = $html->find('div[class~="TimelineItem"]');

        if (is_null($comments)) { // no comments yet
            return;
        }

        foreach ($comments as $comment) {
            $uri = $comment->find('a[href*=#gistcomment]', 0)
                or throwServerException('Could not find comment anchor!');

            $title = $comment->find('h3', 0);

            $datetime = $comment->find('[datetime]', 0)
                or throwServerException('Could not find comment datetime!');

            $author = $comment->find('a.author', 0)
                or throwServerException('Could not find author name!');

            $message = $comment->find('[class~="comment-body"]', 0)
                or throwServerException('Could not find comment body!');

            $item = [];

            $item['uri'] = $uri->href;
            $item['title'] = str_replace('commented', 'commented on', $title->plaintext ?? '');
            $item['timestamp'] = strtotime($datetime->datetime);
            $item['author'] = '<a href="' . $author->href . '">' . $author->plaintext . '</a>';
            $item['content'] = $this->fixContent($message);
            // $item['enclosures'] = array();
            // $item['categories'] = array();

            $this->items[] = $item;
        }
    }

    /** Removes all unnecessary tags and adds formatting */
    private function fixContent($content)
    {
        // Restore code (inside <pre />) highlighting
        foreach ($content->find('pre') as $pre) {
            $pre->style = <<<EOD
padding: 16px;
overflow: auto;
font-size: 85%;
line-height: 1.45;
background-color: #f6f8fa;
border-radius: 3px;
word-wrap: normal;
box-sizing: border-box;
margin-bottom: 16px;
EOD;

            $code = $pre->find('code', 0);

            if ($code) {
                $code->style = <<<EOD
white-space: pre;
word-break: normal;
EOD;
            }
        }

        // find <code /> not inside <pre /> (`inline-code`)
        foreach ($content->find('code') as $code) {
            if ($code->parent()->tag === 'pre') {
                continue;
            }

            $code->style = <<<EOD
background-color: rgba(27,31,35,0.05);
padding: 0.2em 0.4em;
border-radius: 3px;
EOD;
        }

        // restore text spacing
        foreach ($content->find('p') as $p) {
            $p->style = 'margin-bottom: 16px;';
        }

        // Remove unnecessary tags
        $content = strip_tags(
            $content->innertext,
            '<p><a><img><ol><ul><li><table><tr><th><td><string><pre><code><br><hr><h>'
        );

        return $content;
    }
}
