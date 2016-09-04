<?php
require_once('DanbooruBridge.php');

class GelbooruBridge extends DanbooruBridge{

	const MAINTAINER = "mitsukarenai";
	const NAME = "Gelbooru";
	const URI = "http://gelbooru.com/";
	const DESCRIPTION = "Returns images from given page";

    const PATHTODATA='.thumb';
    const IDATTRIBUTE='id';

    const PIDBYPAGE=63;

    protected function getFullURI(){
      return $this->getURI().'index.php?page=post&s=list&'
        .'&pid='.($this->getInput('p')?($this->getInput('p') -1)*static::PIDBYPAGE:'')
        .'&tags='.urlencode($this->getInput('t'));
    }
}
