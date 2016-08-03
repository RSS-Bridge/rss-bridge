<?php
class GuruMedBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "qwertygc";
		$this->name = "GuruMed";
		$this->uri = "http://www.gurumed.org";
		$this->description = "Returns the 5 newest posts from Gurumed (full text)";
		$this->update = "2016-08-03";

	}

    function GurumedStripCDATA($string) {
    	$string = str_replace('<![CDATA[', '', $string);
    	$string = str_replace(']]>', '', $string);
    	return $string;
    }

    function GurumedExtractContent($url) {
	$html2 = $this->file_get_html($url);
	$text = $html2->find('div.entry', 0)->innertext;
	return $text;
    }

    public function collectData(array $param){
        $html = $this->file_get_html('http://gurumed.org/feed') or $this->returnError('Could not request Gurumed.', 404);
	$limit = 0;

	foreach($html->find('item') as $element) {
	 if($limit < 5) {
	 $item = new \Item();
	 $item->title = $this->GurumedStripCDATA($element->find('title', 0)->innertext);
	 $item->uri = $this->GurumedStripCDATA($element->find('guid', 0)->plaintext);
	 $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
	 $item->content = $this->GurumedExtractContent($item->uri);
	 $this->items[] = $item;
	 $limit++;
	 }
	}
    
    }

    public function getName(){
        return 'Gurumed';
    }

    public function getURI(){
        return 'http://gurumed.org/';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}
