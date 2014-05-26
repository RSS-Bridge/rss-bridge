<?php
/**
* RssBridgePlanetLibre
* Returns the 5 newest posts from PlanetLibre (full text)
*
* @name PlanetLibre
* @homepage http://www.www.planet-libre.org
* @description Returns the 5 newest posts from PlanetLibre (full text)
* @maintainer pit-fgfjiudghdf 
* @update 2014-05-26
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
         if($limit < 5) {
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
        return 3600*2; // 1 hour
    }
}

