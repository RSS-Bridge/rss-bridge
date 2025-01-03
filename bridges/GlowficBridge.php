<?php

class GlowficBridge extends BridgeAbstract
{
    const MAINTAINER = 'l1n';
    const NAME = 'Glowfic Bridge';
    const URI = 'https://www.glowfic.com';
    const CACHE_TIMEOUT = 3600; // 1 hour
    const DESCRIPTION = 'Returns the latest replies on a glowfic post.';
    const PARAMETERS = [
        'global' => [],
        'Thread' => [
            'post_id' => [
                'name' => 'Post ID',
                'title' => 'https://www.glowfic.com/posts/POST ID',
                'required' => true,
                'exampleValue' => '2756',
                'type' => 'number'
            ],
            'start_page' => [
                'name' => 'Start Page',
                'title' => 'To start from an offset page',
                'type' => 'number'
            ]
        ]
    ];

    public function collectData()
    {
        $url = $this->getAPIURI();
        $metadata = get_headers($url . '/replies', true);
        $metadata['Last-Page'] = ceil($metadata['Total'] / $metadata['Per-Page']);
        if (
            !is_null($this->getInput('start_page')) &&
            $this->getInput('start_page') < 1 && $metadata['Last-Page'] - $this->getInput('start_page') > 0
        ) {
            $first_page = $metadata['Last-Page'] - $this->getInput('start_page');
        } elseif (!is_null($this->getInput('start_page')) && $this->getInput('start_page') <= $metadata['Last-Page']) {
            $first_page = $this->getInput('start_page');
        } else {
            $first_page = 1;
        }
        for ($page_offset = $first_page; $page_offset <= $metadata['Last-Page']; $page_offset++) {
            $jsonContents = getContents($url . '/replies?page=' . $page_offset);
            $replies = json_decode($jsonContents);
            foreach ($replies as $reply) {
                $item = [];

                $item['content'] = $reply->{'content'};
                $item['uri'] = $this->getURI() . '?page=' . $page_offset . '#reply-' . $reply->{'id'};
                if ($reply->{'icon'}) {
                    $item['enclosures'] = [$reply->{'icon'}->{'url'}];
                }
                $item['author'] = $reply->{'character'}->{'screenname'} . ' (' . $reply->{'character'}->{'name'} . ')';
                $item['timestamp'] = date('r', strtotime($reply->{'created_at'}));
                $item['title'] = 'Tag by ' . $reply->{'user'}->{'username'} . ' updated at ' . $reply->{'updated_at'};
                $this->items[] = $item;
            }
        }
    }

    private function getAPIURI()
    {
        $url = parent::getURI() . '/api/v1/posts/' . $this->getInput('post_id');
        return $url;
    }

    public function getURI()
    {
        $url = parent::getURI() . '/posts/' . $this->getInput('post_id');
        return $url;
    }

    private function getPost()
    {
        $url = $this->getAPIURI();
        $jsonPost = getContents($url);
        $post = json_decode($jsonPost);

        return $post;
    }

    public function getName()
    {
        if (!is_null($this->getInput('post_id'))) {
            $post = $this->getPost();
            return $post->{'subject'} . ' - ' . parent::getName();
        }
        return parent::getName();
    }

    public function getDescription()
    {
        if (!is_null($this->getInput('post_id'))) {
            $post = $this->getPost();
            return $post->{'content'};
        }
        return parent::getName();
    }
}
