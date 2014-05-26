<?php
/**
* RssBridgePlanetLibre
* Returns the 10 newest posts from http://www.www.planet-libre.org (full text)
*
* @name PlanetLibre
* @description Returns the 10 newest posts from PlanetLibre (full text)
*/
class PlanetLibreBridge extends BridgeAbstract{
    public function collectData(array $param){
    function PlanetLibreStripCDATA($string) {
        $string = str_replace('<![CDATA[', '', $string);
        $string = str_replace(']]>', '', $string);
        return $string;
    }
    function PlanetLibreExtractContent($url) {
        $html2 = file_get_html($url);
        $text = $html2->find('div[class=post-text]', 0)->innertext;
        return $text;
    }
        $html = file_get_html('http://www.planet-libre.org/rss10.php') or $this->returnError('Could not request PlanetLibre.', 404);
        $limit = 0;
        foreach($html->find('item') as $element) {
         if($limit < 10) {
         $item = new \Item();
         $item->title = PlanetLibreStripCDATA($element->find('title', 0)->innertext);
         $item->uri = PlanetLibreStripCDATA($element->find('guid', 0)->plaintext);
         $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
         $item->content = PlanetLibreExtractContent($item->uri);
         $this->items[] = $item;
         $limit++;
         }
        }

    }
    public function getName(){
        return 'PlanetLibre';
    }
    public function getURI(){
        return 'http://www.planet-libre.org/';
    }
    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}
