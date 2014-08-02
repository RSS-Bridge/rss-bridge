<?php
/**
* RssBridgeOpenTheory
* Returns the 10 newest posts from http://open1theory.com (full text)
*
* @name Opentheory
* @description Returns the 20 newest posts from OpenTheory (full text)
* @homepage http://open1theory.com
*@maintainer qwertygc
*/
class OpenTheoryBridge extends BridgeAbstract{





    public function collectData(array $param){

    function StripCDATA($string) {
    	$string = str_replace('<![CDATA[', '', $string);
    	$string = str_replace(']]>', '', $string);
    	return $string;
    }
    function ExtractContent($url) {
	$html2 = file_get_html($url);
	$text = $html2->find('div.entry-content', 0)->innertext;
	$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
	return $text;
    }
        $html = file_get_html('http://open1theory.com/feed') or $this->returnError('Could not request OpenTheory.', 404);
	$limit = 0;

	foreach($html->find('item') as $element) {
	 if($limit < 10) {
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
