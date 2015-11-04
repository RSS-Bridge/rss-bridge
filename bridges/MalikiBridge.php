<?php
class MalikiBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "Maliki";
		$this->uri = "http://www.maliki.com/";
		$this->description = "Returns Maliki's newest strips";
		$this->update = "2014-05-30";

	}

    public function collectData(array $param){
        $html = file_get_html('http://www.maliki.com/') or $this->returnError('Could not request Maliki.', 404);
	$count=0;
	$latest=1; $latest_title="";
	$latest = $html->find('div.conteneur_page a', 1)->href;
	$latest_title = $html->find('div.conteneur_page img', 0)->title;

	function MalikiExtractContent($url) {
		$html2 = file_get_html($url);
		$text = 'http://www.maliki.com/'.$html2->find('img', 0)->src;
		$text = '<img alt="" src="'.$text.'"/><br>'.$html2->find('div.imageetnews', 0)->plaintext;
		return $text;
    	}

            $item = new \Item();
            $item->uri = 'http://www.maliki.com/'.$latest;
            $item->title = $latest_title;
            $item->timestamp = time();
            $item->content = MalikiExtractContent($item->uri);
            $this->items[] = $item;
	

        foreach($html->find('div.boite_strip') as $element) {
	  if(!empty($element->find('a',0)->href) and $count < 3) {
            $item = new \Item();
            $item->uri = 'http://www.maliki.com/'.$element->find('a',0)->href;
            $item->title = $element->find('img',0)->title;
            $item->timestamp = strtotime(str_replace('/', '-', $element->find('span.stylepetit', 0)->innertext));
            $item->content = MalikiExtractContent($item->uri);
            $this->items[] = $item;
	    $count++;
          }
        }
    }

    public function getName(){
        return 'Maliki';
    }

    public function getURI(){
        return 'http://www.maliki.com/';
    }

    public function getCacheDuration(){
        return 86400*6; // 6 days
    }
}
