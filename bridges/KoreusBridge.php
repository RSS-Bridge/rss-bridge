<?php
class KoreusBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "pit-fgfjiudghdf";
		$this->name = "Koreus";
		$this->uri = "http://www.koreus.com/";
		$this->description = "Returns the 5 newest posts from Koreus (full text)";
		$this->update = "2014-05-26";

	}

    public function collectData(array $param){

    function KoreusStripCDATA($string) {
        $string = str_replace('<![CDATA[', '', $string);
        $string = str_replace(']]>', '', $string);
        return $string;
    }
    function KoreusExtractContent($url) {
        $html2 = $this->file_get_html($url);
        $text = $html2->find('p[class=itemText]', 0)->innertext;
        $text = utf8_encode(preg_replace('/(Sur le m.+?)+$/i','',$text));
        return $text;
    }
        $html = $this->file_get_html('http://feeds.feedburner.com/Koreus-articles') or $this->returnError('Could not request Koreus.', 404);
        $limit = 0;

        foreach($html->find('item') as $element) {
         if($limit < 5) {
         $item = new \Item();
         $item->title = KoreusStripCDATA($element->find('title', 0)->innertext);
         $item->uri = KoreusStripCDATA($element->find('guid', 0)->plaintext);
         $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
         $item->content = KoreusExtractContent($item->uri);
         $this->items[] = $item;
         $limit++;
         }
        }

    }

    public function getName(){
        return 'Koreus';
    }

    public function getURI(){
        return 'http://www.koreus.com/';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}

