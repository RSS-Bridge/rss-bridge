<?php
/**
* RssBridgeGuruMed 
* Returns the 10 newest posts from http://www.gurumed.org (full text)
*
* @name GuruMed
* @description Returns the 20 newest posts from Gurumed (full text)
*/
class GuruMedBridge extends BridgeAbstract{





    public function collectData(array $param){

    function GurumedStripCDATA($string) {
    	$string = str_replace('<![CDATA[', '', $string);
    	$string = str_replace(']]>', '', $string);
    	return $string;
    }
    function GurumedExtractContent($url) {
	$html2 = file_get_html($url);
	$text = $html2->find('div.entry', 0)->innertext;
	return $text;
    }
        $html = file_get_html('http://gurumed.org/feed') or $this->returnError('Could not request Gurumed.', 404);
	$limit = 0;

	foreach($html->find('item') as $element) {
	 if($limit < 10) {
	 $item = new \Item();
	 $item->title = GurumedStripCDATA($element->find('title', 0)->innertext);
	 $item->uri = GurumedStripCDATA($element->find('guid', 0)->plaintext);
	 $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
	 $item->content = GurumedExtractContent($item->uri);
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
