<?php
class TagBoardBridge extends BridgeAbstract{

	const MAINTAINER = "Pitchoule";
	const NAME = "TagBoard";
	const URI = "http://www.TagBoard.com/";
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = "Returns most recent results from TagBoard.";

    const PARAMETERS = array( array(
        'u'=>array(
            'name'=>'keyword',
            'required'=>true
        )
    ));

    public function collectData(){
        $link = 'https://post-cache.tagboard.com/search/' .$this->getInput('u');

        $html = getSimpleHTMLDOM($link)
          or returnServerError('Could not request TagBoard for : ' . $link);
        $parsed_json = json_decode($html);

        foreach($parsed_json->{'posts'} as $element) {
                $item = array();
                $item['uri'] = $element->{'permalink'};
		$item['title'] = $element->{'text'};
                $thumbnailUri = $element->{'photos'}[0]->{'m'};
                if (isset($thumbnailUri)) {
                  $item['content'] = '<a href="' . $item['uri'] . '"><img src="' . $thumbnailUri . '" /></a>';
                }else{
                  $item['content'] = $element->{'html'};
                }
                $this->items[] = $item;
        }
    }

    public function getName(){
        return 'tagboard - ' .$this->getInput('u');
    }
}

