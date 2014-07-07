<?php
/**
* @name MemoLinux
* @description Returns the 20 newest posts from MemoLinux (full text)
*@maintainer qwertygc
*/
class MemoLinuxBridge extends BridgeAbstract{





    public function collectData(array $param){

    function StripCDATA($string) {
    	$string = str_replace('<![CDATA[', '', $string);
    	$string = str_replace(']]>', '', $string);
    	return $string;
    }
    function ExtractContent($url) {
	$html2 = file_get_html($url);
	$text = $html2->find('div.post-content', 0)->innertext;
	$text = preg_replace('@<script[^>]*?>.*?</script>@si', '', $text);
	$text = preg_replace('@<div[^>]*?>.*?</div>@si', '', $text);
	return $text;
    }
        $html = file_get_html('http://memo-linux.com/feed/') or $this->returnError('Could not request MemoLinux.', 404);
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
        return 'MemoLinux';
    }

    public function getURI(){
        return 'http://memo-linux.com/feed/';
    }

    public function getCacheDuration(){
        // return 3600; // 1 hour
        return 0; // 1 hour
    }
}
