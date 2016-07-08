<?php
class OpenTheoryBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "qwertygc";
		$this->name = "Opentheory";
		$this->uri = "http://open1theory.com";
		$this->description = "Returns the 5 newest posts from OpenTheory (full text)";
		$this->update = "02-08-2014";

	}

    public function collectData(array $param){

    function StripCDATA($string) {
    	$string = str_replace('<![CDATA[', '', $string);
    	$string = str_replace(']]>', '', $string);
    	return $string;
    }
    function ExtractContent($url) {
	$html2 = $this->getSimpleHTMLDOM($url);
	$text = $html2->find('div.entry-content', 0)->innertext;
	$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
	return $text;
    }
        $html = $this->getSimpleHTMLDOM('http://open1theory.com/feed') or $this->returnError('Could not request OpenTheory.', 404);
	$limit = 0;

	foreach($html->find('item') as $element) {
	 if($limit < 5) {
	 $item = new \Item();
	 $item->title = StripCDATA($element->find('title', 0)->innertext);
	 $item->uri = StripCDATA($element->find('guid', 0)->plaintext);
	 $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
	 $item->content = ExtractContent($item->uri);
	 $this->items[] = $item;
	 $limit++;
	 }
	}
    
    }

    public function getName(){
        return 'OpenTheory';
    }

    public function getURI(){
        return 'http://open1theory.com/feed';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
        // return 0; // 1 hour
    }
}
