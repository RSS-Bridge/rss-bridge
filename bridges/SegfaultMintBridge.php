<?php
/**
* @name SegfaultMint
* @homepage http://segfault.linuxmint.com/
* @description Returns the 5 newest posts from SegfaultMint (full text)
* @maintainer qwertygc
* @update 2014-07-05
*/
class SegfaultMintBridge extends BridgeAbstract{





    public function collectData(array $param){

    function StripCDATA($string) {
    	$string = str_replace('<![CDATA[', '', $string);
    	$string = str_replace(']]>', '', $string);
    	return $string;
    }
    function ExtractContent($url) {
	$html2 = file_get_html($url);
	$text = $html2->find('div.post-bodycopy', 0)->innertext;
	$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
	return $text;
    }
        $html = file_get_html('http://segfault.linuxmint.com/feed/') or $this->returnError('Could not request segfault.', 404);
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
        return 'Segfault Mint';
    }

    public function getURI(){
        return 'http://segfault.linuxmint.com/feed/';
    }

    public function getCacheDuration(){
        return 3600*24; // 24 hours
    }
}
