<?php
/**
* RssBridgeRaymond
* Returns the 3 newest posts from Raymond.cc (full text)
*
* @name Raymond
* @homepage http://www.raymond.cc
* @description Returns the 3 newest posts from Raymond.cc (full text)
* @maintainer pit-fgfjiudghdf
* @update 2014-05-26
*/
class RaymondBridge extends BridgeAbstract{
    public function collectData(array $param){
    function raymondStripCDATA($string) {
        $string = str_replace('<![CDATA[', '', $string);
        $string = str_replace(']]>', '', $string);
        return $string;
    }
    function raymondExtractContent($url) {
        $html2 = file_get_html($url);
        $text = $html2->find('div.entry-content', 0)->innertext;
	$text = preg_replace('/class="ad".*/', '', $text);
	$text = strip_tags($text, '<p><a><i><strong><em><img>');
	$text = str_replace('(adsbygoogle = window.adsbygoogle || []).push({});', '', $text);
        return $text;
    }
        $html = file_get_html('http://www.raymond.cc/blog/feed') or $this->returnError('Could not request raymond.', 404);
        $limit = 0;
        foreach($html->find('item') as $element) {
         if($limit < 3) {
         $item = new \Item();
         $item->title = raymondStripCDATA($element->find('title', 0)->innertext);
         $item->uri = raymondStripCDATA($element->find('guid', 0)->plaintext);
         $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
         $item->content = raymondExtractContent($item->uri);
         $this->items[] = $item;
         $limit++;
         }
        }

    }
    public function getName(){
        return 'raymond';
    }
    public function getURI(){
        return 'http://www.raymond.cc/blog';
    }
    public function getCacheDuration(){
        return 3600*12; // 12 hour
    }
}

