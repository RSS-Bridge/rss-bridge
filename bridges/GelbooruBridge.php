<?php

class GelbooruBridge extends BridgeAbstract
{
    const MAINTAINER = 'mitsukarenai';
    const NAME = 'Gelbooru';
    const URI = 'https://gelbooru.com/';
    const DESCRIPTION = 'Returns images from given page';

    const PARAMETERS = [
        'global' => [
            'p' => [
                'name' => 'page',
                'defaultValue' => 0,
                'type' => 'number'
            ],
            't' => [
                'name' => 'tags',
                'exampleValue' => 'solo',
                'title' => 'Tags to search for'
            ],
            'l' => [
                'name' => 'limit',
                'exampleValue' => 100,
                'title' => 'How many posts to retrieve (hard limit of 1000)'
            ]
        ],
        0 => []
    ];

    protected function getFullURI()
    {
        return $this->getURI()
        . 'index.php?&page=dapi&s=post&q=index&json=1&pid=' . $this->getInput('p')
        . '&limit=' . $this->getInput('l')
        . '&tags=' . urlencode($this->getInput('t'));
    }

    /*
    This function is superfluous for GelbooruBridge, but useful
    for Bridges that inherit from it
    */
    protected function buildThumbnailURI($element)
    {
        return $this->getURI() . 'thumbnails/' . $element->directory
        . '/thumbnail_' . $element->md5 . '.jpg';
    }

    protected function getItemFromElement($element)
    {
        $item = [];
        $item['uri'] = $this->getURI() . 'index.php?page=post&s=view&id='
        . $element->id;
        $item['postid'] = $element->id;
        $item['author'] = $element->owner;
        $item['timestamp'] = date('d F Y H:i:s', $element->change);
        $item['tags'] = $element->tags;
        $item['title'] = $this->getName() . ' | ' . $item['postid'];

        if (isset($element->preview_url)) {
            $thumbnailUri = $element->preview_url;
        } else {
            $thumbnailUri = $this->buildThumbnailURI($element);
        }

        $item['content'] = '<a href="' . $item['uri'] . '"><img src="'
        . $thumbnailUri . '" /></a><br><br><b>Tags:</b> '
        . $item['tags'] . '<br><br>' . $item['timestamp'];

        return $item;
    }

    public function collectData()
    {
        $content = getContents($this->getFullURI());
        // $content is empty string

        // Most other Gelbooru-based boorus put their content in the root of
        // the JSON. This check is here for Bridges that inherit from this one
        $posts = json_decode($content);
        if (isset($posts->post)) {
            $posts = $posts->post;
        }

        if (is_null($posts)) {
            returnServerError('No posts found.');
        }

        foreach ($posts as $post) {
            $this->items[] = $this->getItemFromElement($post);
        }
    }
}
