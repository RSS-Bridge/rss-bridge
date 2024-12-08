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
        . '&tags=' . urlencode($this->getInput('t') ?? '');
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
        . $thumbnailUri . '" /></a><br><br><b>Dimensions:</b> '
        . strval($element->width) . ' x ' . strval($element->height) . '<br><br><b>Tags:</b> '
        . $item['tags'];
        if (isset($element->source)) {
            $item['content'] .= '<br><br><b>Source: </b><a href="' . $element->source . '">' . $element->source . '</a>';
        }

        return $item;
    }

    public function collectData()
    {
        $url = $this->getFullURI();
        $content = getContents($url);

        if ($content === '') {
            return;
        }

        $posts = Json::decode($content, false);
        if (isset($posts->post)) {
            $posts = $posts->post;
        }

        foreach ($posts as $post) {
            $this->items[] = $this->getItemFromElement($post);
        }
    }
}
