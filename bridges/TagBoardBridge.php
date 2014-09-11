<?php
/**
* RssBridgeTagBoard
* Search TagBoard for most recent pages regarding a specific topic.
* Returns the most recent links in results, sorting by date (most recent first).
* 2014-09-10
*
* @name TagBoard
* @homepage http://www.TagBoard.com
* @description Returns most recent results from TagBoard.
* @maintainer Pitchoule
* @use1(u="keyword")
*/
class TagBoardBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';
        $this->request = $param['u'];
        $link = 'https://post-cache.tagboard.com/search/' .$this->request;
		
        $html = file_get_html($link) or $this->returnError('Could not request TagBoard for : ' . $link , 404);
        $parsed_json = json_decode($html);

        foreach($parsed_json->{'posts'} as $element) {
                $item = new Item();
                $item->uri = $element->{'permalink'};
		$item->title = $element->{'text'};
                $item->thumbnailUri = $element->{'photos'}[0]->{'m'};
                if (isset($item->thumbnailUri)) {
                  $item->content = '<a href="' . $item->uri . '"><img src="' . $item->thumbnailUri . '" /></a>';
                }else{
                  $item->content = $element->{'html'};
                }
                $this->items[] = $item;
        }
    }

    public function getName(){
        return 'tagboard - ' .$this->request;
    }

    public function getURI(){
        return 'http://TagBoard.com';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
							
