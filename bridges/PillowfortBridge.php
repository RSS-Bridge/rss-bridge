<?php

class PillowfortBridge extends BridgeAbstract
{
    const NAME = 'Pillowfort';
    const URI = 'https://www.pillowfort.social';
    const DESCRIPTION = 'Returns recent posts from a user';
    const MAINTAINER = 'KamaleiZestri';
    const PARAMETERS = [[
        'username' => [
            'name' => 'Username',
            'type' => 'text',
            'required' => true,
            'exampleValue'  => 'Staff'
        ],
        'noava' => [
            'name' => 'Hide avatar',
            'type' => 'checkbox',
            'title' => 'Check to hide user avatars.'
        ],
        'noreblog' => [
            'name' => 'Hide reblogs',
            'type' => 'checkbox',
            'title' => 'Check to only show original posts.'
        ],
        'noretags' => [
            'name' => 'Prefer original tags',
            'type' => 'checkbox',
            'title' => 'Check to use tags from original post(if available) instead of reblog\'s tags'
        ],
        'image' => [
            'name' => 'Select image type',
            'type' => 'list',
            'title' => 'Decides how the image is displayed, if at all.',
            'values' => [
                'None' => 'None',
                'Small' => 'Small',
                'Full' => 'Full'
            ],
            'defaultValue' => 'Full'
        ]
    ]];

    /**
     * The Pillowfort bridge.
     *
     * Pillowfort pages are dynamically generated from a json file
     * which holds the last 20 or so posts from the given user.
     * This bridge uses that json file and HTML/CSS similar
     * to the Twitter bridge for formatting.
     */
    public function collectData()
    {
        $jsonSite = getContents($this->getJSONURI());

        $jsonFile = json_decode($jsonSite, true);
        $posts = $jsonFile['posts'];

        foreach ($posts as $post) {
            $item = $this->getItemFromPost($post);

            //empty when 'noreblogs' is checked and current post is a reblog.
            if (!empty($item)) {
                $this->items[] = $item;
            }
        }
    }

    public function getName()
    {
        $name = $this -> getUsername();
        if ($name != '') {
            return $name . ' - ' . self::NAME;
        } else {
            return parent::getName();
        }
    }

    public function getURI()
    {
        $name = $this -> getUsername();
        if ($name != '') {
            return self::URI . '/' . $name;
        } else {
            return parent::getURI();
        }
    }

    protected function getJSONURI()
    {
        return $this -> getURI() . '/json/?p=1';
    }

    protected function getUsername()
    {
        return $this -> getInput('username');
    }

    protected function genAvatarText($author, $avatar_url, $title)
    {
        $noava = $this -> getInput('noava');

        if ($noava) {
            return '';
        } else {
            return <<<EOD
<a href="{self::URI}/posts/{$author}">
<img
	style="align:top; width:75px; border:1px solid black;"
	alt="{$author}"
	src="{$avatar_url}"
	title="{$title}" />
</a>
EOD;
        }
    }

    protected function genImagesText($media)
    {
        $dimensions = $this -> getInput('image');
        $text = '';

        //preg_replace used for images with spaces in the url

        switch ($dimensions) {
            case 'None':
                foreach ($media as $image) {
                    $imageURL = preg_replace('[ ]', '%20', $image['url']);
                    $text .= <<<EOD
<a href="{$imageURL}">
	{$imageURL}
</a>
EOD;
                }
                break;

            case 'Small':
                foreach ($media as $image) {
                    $imageURL = preg_replace('[ ]', '%20', $image['small_image_url']);
                    $text .= <<<EOD
<a href="{$imageURL}">
	<img
		style="align:top; max-width:558px; border:1px solid black;"
		src="{$imageURL}" 
	/>
</a>
EOD;
                }
                break;

            case 'Full':
                foreach ($media as $image) {
                    $imageURL = preg_replace('[ ]', '%20', $image['url']);
                    $text .= <<<EOD
<a href="{$imageURL}">
	<img
		style="align:top; max-width:558px; border:1px solid black;"
		src="{$imageURL}" 
	/>
</a>
EOD;
                }
                break;

            default:
                break;
        }

        return $text;
    }

    protected function getItemFromPost($post)
    {
        //check if its a reblog.
        if ($post['original_post_id'] == null) {
            $embPost = false;
        } else {
            $embPost = true;
        }

        if ($this -> getInput('noreblog') && $embPost) {
            return [];
        }

        $item = [];

        $item['uid'] = $post['id'];
        $item['timestamp'] = strtotime($post['created_at']);

        if ($embPost) {
            $item['uri'] = self::URI . '/posts/' . $post['original_post']['id'];
            $item['author'] = $post['original_username'];
            if ($post['original_post']['title'] != '') {
                $item['title'] = $post['original_post']['title'];
            } else {
                $item['title'] = '[NO TITLE]';
            }
        } else {
            $item['uri'] = self::URI . '/posts/' . $post['id'];
            $item['author'] = $post['username'];
            if ($post['title'] != '') {
                $item['title'] = $post['title'];
            } else {
                $item['title'] = '[NO TITLE]';
            }
        }

        /**
         * 4 cases if it is a reblog.
         * 1: reblog has tags, original has tags. defer to option.
         * 2: reblog has tags, original has no tags. use reblog tags.
         * 3: reblog has no tags, original has tags. use original tags.
         * 4: reblog has no tags, original has no tags. use reblog tags not that it matters.
         */
        $item['categories'] = $post['tags'];
        if ($embPost) {
            if ($this -> getInput('noretags') || ($post['tags'] == null)) {
                $item['categories'] = $post['original_post']['tag_list'];
            }
        }

        $avatarText = $this -> genAvatarText(
            $item['author'],
            $post['avatar_url'],
            $item['title']
        );
        $imagesText = $this -> genImagesText($post['media']);

        $item['content'] = <<<EOD
<div style="display: inline-block; vertical-align: top;">
	{$avatarText}
</div>
<div style="display: inline-block; vertical-align: top;">
	{$post['content']}
</div>
<div style="display: block; vertical-align: top;">
	{$imagesText}
</div>
EOD;

        return $item;
    }
}
