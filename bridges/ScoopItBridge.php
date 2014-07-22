<?php
/**
* RssBridgeScoopIt
* Search DScoopIt for most recent pages regarding a specific topic.
* Returns the most recent links in results, sorting by date (most recent first).
* 2014-06-13
*
* @name ScoopIt
* @homepage http://www.scoop.it
* @description Returns most recent results from ScoopIt.
* @maintainer Pitchoule
* @use1(u="keyword")
*/
class ScoopItBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';
		    $this->request = $param['u'];
        $link = 'http://scoop.it/search?q=' .urlencode($this->request);
		
        $html = file_get_html($link) or $this->returnError('Could not request ScoopIt. for : ' . $link , 404);
		
        foreach($html->find('div.post-view') as $element) {
                $item = new Item();
                $item->uri = $element->find('a', 0)->href;
                $item->title = $element->find('div.tCustomization_post_title',0)->innertext;
                $item->content = $element->find('div.tCustomization_post_description', 0)->plaintext;
                $this->items[] = $item;
        }
    }

    public function getName(){
        return 'ScooptIt';
    }

    public function getURI(){
        return 'http://Scoop.it';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
