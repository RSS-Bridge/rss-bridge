<?php
class TagBoardBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "Pitchoule";
		$this->name = "TagBoard";
		$this->uri = "http://www.TagBoard.com";
		$this->description = "Returns most recent results from TagBoard.";
		$this->update = "2016-08-09";

		$this->parameters[] =
		'[
			{
				"name" : "keyword",
				"identifier" : "u"
			}
		]';

	}

    public function collectData(array $param){
        $html = '';
        $this->request = $param['u'];
        $link = 'https://post-cache.tagboard.com/search/' .$this->request;
		
        $html = $this->file_get_html($link) or $this->returnError('Could not request TagBoard for : ' . $link , 404);
        $parsed_json = json_decode($html);

        foreach($parsed_json->{'posts'} as $element) {
                $item = new Item();
                $item->uri = $element->{'permalink'};
		$item->title = $element->{'text'};
                $thumbnailUri = $element->{'photos'}[0]->{'m'};
                if (isset($thumbnailUri)) {
                  $item->content = '<a href="' . $item->uri . '"><img src="' . $thumbnailUri . '" /></a>';
                }else{
                  $item->content = $element->{'html'};
                }
                $this->items[] = $item;
        }
    }

    public function getName(){
        return 'tagboard - ' .$this->request;
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
							
