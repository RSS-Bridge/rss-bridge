<?php
/**
* RssBridgeNiceMatin 
* Returns the 10 newest posts from Nice Matin (full text)
*
* @name NiceMatin
* @homepage http://www.nicematin.com/
* @description Returns the 10 newest posts from NiceMatin (full text)
* @maintainer pit-fgfjiudghdf
* @update 2014-05-26
*/
class NiceMatinBridge extends BridgeAbstract{

    public function collectData(array $param){

    function NiceMatinUrl($string) {
        $string = str_replace('</link>', '', $string);
        //$string = str_replace('.+', '', $string);
        $string = preg_replace('/html.*http.*/i','html',$string);
        $string = preg_replace('/.*http/i','http',$string);
        return $string;
    }

    function NiceMatinExtractContent($url) {
        $html2 = file_get_html($url);
        $text = $html2->find('figure[itemprop=associatedMedia]', 0)->innertext;
        $text .= $html2->find('div[id=content-article]', 0)->innertext;
        return $text;
    }

        $html = file_get_html('http://www.nicematin.com/derniere-minute/rss') or $this->returnError('Could not request NiceMatin.', 404);
        $limit = 0;

        foreach($html->find('item') as $element) {
         if($limit < 10) {
         $item = new \Item();
         //$item->title = NiceMatinStripCDATA($element->find('title', 0)->innertext);
         $item->title = $element->find('title', 0)->innertext;
         $item->uri = NiceMatinUrl($element->plaintext);

         $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
         $item->content = NiceMatinExtractContent($item->uri);
         $this->items[] = $item;
         $limit++;
         }
        }

    }

    public function getName(){
        return 'NiceMatin';
    }

    public function getURI(){
        return 'http://www.nicematin.com/';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}

