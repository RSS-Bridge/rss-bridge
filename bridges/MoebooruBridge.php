<?php

class MoebooruBridge extends BridgeAbstract
{
    const NAME = 'Moebooru';
    const URI = 'https://moe.dev.myconan.net/';
    const CACHE_TIMEOUT = 1800; // 30min
    const DESCRIPTION = 'Returns images from given page';
    const MAINTAINER = 'pmaziere';

    const PARAMETERS = [ [
        'p' => [
            'name' => 'page',
            'defaultValue' => 1,
            'type' => 'number'
        ],
        't' => [
            'name' => 'tags'
        ]
    ]];

    protected function getFullURI()
    {
        return $this->getURI()
        . 'post?page='
        . $this->getInput('p')
        . '&tags='
        . urlencode($this->getInput('t'));
    }

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getFullURI());

        $input_json = explode('Post.register(', $html);
        foreach ($input_json as $element) {
            $data[] = preg_replace('/}\)(.*)/', '}', $element);
        }
        unset($data[0]);

        foreach ($data as $datai) {
            $json = json_decode($datai, true);
            $item = [];
            $item['uri'] = $this->getURI() . '/post/show/' . $json['id'];
            $item['postid'] = $json['id'];
            $item['timestamp'] = $json['created_at'];
            $item['imageUri'] = $json['file_url'];
            $item['title'] = $this->getName() . ' | ' . $json['id'];
            $item['content'] = '<a href="'
            . $item['imageUri']
            . '"><img src="'
            . $json['preview_url']
            . '" /></a><br>Tags: '
            . $json['tags'];

            $this->items[] = $item;
        }
    }
}
