<?php
class TagBoardBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "Pitchoule";
		$this->name = "TagBoard";
		$this->uri = "http://www.TagBoard.com";
		$this->description = "Returns most recent results from TagBoard.";

        $this->parameters[] = array(
          'u'=>array(
            'name'=>'keyword',
            'required'=>true
          )
        );

	}

    public function collectData(array $param){
        $html = '';
        $this->request = $param['u'];
        $link = 'https://post-cache.tagboard.com/search/' .$this->request;

        $html = $this->getSimpleHTMLDOM($link) or $this->returnServerError('Could not request TagBoard for : ' . $link);
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
        return 'tagboard - ' .$this->request;
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}

