<?php
/*
**
* RssBridgeBasta
* Returns the newest articles
*
* @name BastaMag! Bridge
* @description Returns the newest articles.
*/
class BastaBridge extends BridgeAbstract{
    
        public function collectData(array $param){

			
		function BastaExtractContent($url) {
		$html2 = file_get_html($url);
		$text = $html2->find('div.texte', 0)->innertext;
		return $text;
		}
		$html = file_get_html('http://www.bastamag.net/spip.php?page=backend') or $this->returnError('Could not request Bastamag.', 404);
		$limit = 0;

		foreach($html->find('item') as $element) {
		 if($limit < 10) {
		 $item = new \Item();
		 $item->title = $element->find('title', 0)->innertext;
		 $item->uri = $element->find('guid', 0)->plaintext;
		 $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
		 $item->content = BastaExtractContent($item->uri);
		 $this->items[] = $item;
		 $limit++;
		 }
		}
    
    }

    public function getName(){
        return 'Bastamag Bridge';
    }

    public function getURI(){
        return 'http://bastamag.net/';
    }

    public function getCacheDuration(){
        return 3600*2; // 2 hours
        // return 0; // 2 hours
    }
}
