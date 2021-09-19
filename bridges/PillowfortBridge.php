<?php
class PillowfortBridge extends BridgeAbstract {
    const NAME = 'Pillowfort';
    const URI = 'https://www.pillowfort.social';
    const DESCRIPTION = 'Returns recent posts from a user';
    const MAINTAINER = 'KamaleiZestri';
    const PARAMETERS = array(array(
        'username' => array(
            'name' => 'Username',
            'type' => 'text',
            'required' => true
        )
    ));

    public function getName()
    {
        $name = $this -> getUsername();
        if($name !='')
            return $name . ' - ' . self::NAME;
        else
            return parent::getName();
    }

    public function getURI()
    {
        $name = $this -> getUsername();
        if($name !='')
            return self::URI . '/' . $name;
        else
            return parent::getURI();
    }

    protected function getJSONURI() {
        return $this -> getURI() . '/json';
    }

    protected function getUsername() {
        return $this -> getInput('username');
    }

    protected function getItemFromPost($post) {
        //TODO copy twitter bridge options: (1) scale/(2)hide images, (3)hide reblogs, (4) hide avatars
        //will also need html formatting to make it look good.

        //check if its a reblog.
        if($post['original_post_id'] == null)
            $embPost = false;
        else
            $embPost = true;

        $item = array();

        $item['uid'] = $post['id'];
        $item['timestamp'] = strtotime($post['created_at']);
        
        if($embPost)
        {
            $item['uri'] = self::URI . '/posts/' . $post['original_post']['id'];
            $item['author'] = $post['original_username'];
            if($post['original_post']['title'] != '')
                $item['title'] = $post['original_post']['title'];
            else
                $item['title'] = '[NO TITLE]';
        }
        else
        {
            $item['uri'] = self::URI . '/posts/' . $post['id'];
            $item['author'] = $post['username'];
            if($post['title'] != '')
                $item['title'] = $post['title'];
            else
                $item['title'] = '[NO TITLE]';
        }

        //TODO when post has no tags, use original posts' tags. should this be an option?
        if(!$embPost)
            $item['tags'] = $post['tags'];
        else if($embPost && $post['tags'] !=null) //when post and original post both have tags. use post tags.
            $item['tags'] = $post['tags'];
        else if($embPost && $post['tags'] == null) //when post has no tags and original post has tags. OPTION. default, use orignal posts
            $item['tags'] = $post['original_post']['tag_list'];
        else    //when post has tags and original post has no tags.
            $item['tags'] = $post['$tags'];


        $avatarText = <<<EOD
<a href="{self::URI}/posts/{$item['author']}">
<img
	style="align:top; width:75px; border:1px solid black;"
	alt="{$item['author']}"
	src="{$post['avatar_url']}"
	title="{$item['title']}" />
</a>
EOD;
        $imagesText = '';

        //TODO option to load images or not? option to load big or small versions? pillowfort users can upload alot of images.
        foreach($post['media'] as $image)
        {
            $imageURL = preg_replace('[ ]', '%20', $image['url']);  //for images with spaces in url
            $imagesText .= <<<EOD
<a href="{$imageURL}">
    <img
        style="align:top; max-width:558px; border:1px solid black;"
        src="{$imageURL}" 
    />
</a>
EOD;
        }
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

    public function collectData() {
        // Pillowfort pages are dynamically generated from this json file.
        $jsonSite = getContents($this -> getJSONURI())
            or returnServerError('Could not get the feed of' . $this->getUsername());

        $jsonFile = json_decode($jsonSite, true);
        $posts = $jsonFile['posts'];

        foreach($posts as $post)
            $this->items[] = $this->getItemFromPost($post);
    }
}
