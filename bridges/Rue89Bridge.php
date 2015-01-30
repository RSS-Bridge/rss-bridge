<?php
/**
* RssBridgeRue89
* Returns the 5 newest posts from http://rue89.nouvelobs.com/ (full text)
*
* @name Rue89
* @description Returns the 5 newest posts from Rue89 (full text)
*/
class Rue89Bridge extends BridgeAbstract{
    public function collectData(array $param){
    function Rue89StripCDATA($string) {
        $string = str_replace('<![CDATA[', '', $string);
        $string = str_replace(']]>', '', $string);
        return $string;
    }
    function Rue89ExtractContent($url) {
        $html2 = file_get_html($url);
        //$text = $html2->find('div[class=text]', 0)->innertext;
        $text = $html2->find('div article', 0)->innertext;
        //$text = $html2->find('div.article', 0)->innertext;
        //$text = $html2->find('div[id=main]', 0)->innertext;
        //$text = $html2->find('div[id=article]', 0)->innertext;
        //$text = preg_replace('/(<div class="gallery-thumbnail">.+?)+(<\/div>)/i','',$text);
        return $text;
    }
        $html = file_get_html('http://rue89.feedsportal.com/c/33822/f/608948/index.rss') or $this->returnError('Could not request Rue89.', 404);
        $limit = 0;
        foreach($html->find('item') as $element) {
         if($limit < 5) {
         $item = new \Item();
         $item->title = Rue89StripCDATA($element->find('title', 0)->innertext);
         $item->uri = Rue89StripCDATA($element->find('guid', 0)->plaintext);
         $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
         $item->content = Rue89ExtractContent($item->uri);
         $this->items[] = $item;
         $limit++;
         }
        }

    }
    public function getName(){
        return 'Rue89';
    }
    public function getURI(){
        return 'http://rue89.nouvelobs.com/';
    }
    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}
