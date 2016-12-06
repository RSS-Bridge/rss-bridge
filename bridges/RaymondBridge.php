<?php
/**
* RssBridgeRaymond
* Returns the 10 newest posts from http://www.www.raymond.cc (full text)
*
* @name Raymond
* @description Returns the 20 newest posts from raymond (full text)
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
        $text = str_replace('<noscript>', '', $text);
        $text = str_replace('</noscript>', '', $text);
        $text = str_replace('<script*<.script>', '', $text);
        $text = str_replace('script*.script', '', $text);
        $text = str_replace('&zwnj;', '', $text);
        $text = str_replace('&lt;', '<', $text);
        return $text;
    }
        $html = file_get_html('http://www.raymond.cc/blog/feed') or $this->returnError('Could not request raymond.', 404);
        $limit = 0;
        foreach($html->find('item') as $element) {
         if($limit < 20) {
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
        return 3600; // 1 hour
    }
}
