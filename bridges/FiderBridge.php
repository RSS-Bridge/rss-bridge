<?php

class FiderBridge extends BridgeAbstract
{
    const NAME = 'Fider Bridge';
    const URI = 'https://fider.io/';
    const DESCRIPTION = 'Bridge for any Fider instance';
    const MAINTAINER = 'Oliver Nutter';
    const PARAMETERS = [
        'global' => [
            'instance' => [
                'name' => 'Instance URL',
                'required' => true,
                'example' => 'https://feedback.fider.io',
            ],
        ],
        'Post' => [
            'num' => [
                'name' => 'Post Number',
                'type' => 'number',
                'required' => true,
            ],
            'limit' => [
                'name' => 'Number of comments to return',
                'type' => 'number',
                'required' => false,
                'title' => 'Specify number of comments to return',
            ],
        ],
    ];

    private $instance;
    private $posturi;
    private $title;

    public function getName()
    {
        return $this->title ?? parent::getName();
    }

    public function getURI()
    {
        return $this->posturi ?? parent::getURI();
    }

    protected function setTitle($title)
    {
        $html = getSimpleHTMLDOMCached($this->instance);
        $name = $html->find('title', 0)->innertext;

        $this->title = "$title - $name";
    }

    protected function getItem($post, $response = false, $first = false)
    {
        $item = [];
        $item['uri'] = $this->getURI();
        $item['timestamp'] = $response ? $post->respondedAt : $post->createdAt;
        $item['author'] = $post->user->name;

        $datetime = new DateTime($item['timestamp']);
        if ($response) {
            $item['uid'] = 'response';
            $item['content'] = $post->text;
            $item['title'] = "{$item['author']} marked as $post->status {$datetime->format('M d, Y')}";
        } elseif ($first) {
            $item['uid'] = 'post';
            $item['content'] = $post->description;
            $item['title'] = $post->title;
        } else {
            $item['uid'] = 'comment';
            $item['content'] = $post->content;
            $item['title'] = "{$item['author']} commented {$datetime->format('M d, Y')}";
        }

        $item['uid'] .= $item['author'] . $item['timestamp'];

        // parse markdown with implicit line breaks
        $item['content'] = markdownToHtml($item['content'], ['breaksEnabled' => true]);

        if (property_exists($post, 'editedAt')) {
            $item['title'] .= ' (edited)';
        }

        if ($first) {
            $item['categories'] = $post->tags;
        }

        return $item;
    }

    public function collectData()
    {
        // collect first post
        $this->instance = rtrim($this->getInput('instance'), '/');

        $num = $this->getInput('num');
        $this->posturi = "$this->instance/posts/$num";

        $post_api_uri = "$this->instance/api/v1/posts/$num";
        $post = json_decode(getContents($post_api_uri));

        $this->setTitle($post->title);

        $item = $this->getItem($post, false, true);
        $this->items[] = $item;

        // collect response to first post
        if (property_exists($post, 'response')) {
            $response = $post->response;
            $response->status = $post->status;
            $this->items[] = $this->getItem($response, true);
        }

        // collect comments
        $comment_api_uri = "$post_api_uri/comments";
        $comments = json_decode(getContents($comment_api_uri));

        foreach ($comments as $post) {
            $item = $this->getItem($post);
            $this->items[] = $item;
        }

        usort($this->items, function ($a, $b) {
            return $b['timestamp'] <=> $a['timestamp'];
        });

        if ($this->getInput('limit') ?? 0 > 0) {
            $this->items = array_slice($this->items, 0, $this->getInput('limit'));
        }
    }
}
