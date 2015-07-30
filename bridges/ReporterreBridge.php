<?php
/**
* RssBridgeReporterre
* Returns the newest article
* 2015-04-07
*
* @name Reporterre Bridge
* @homepage http://www.reporterre.net/
* @description Returns the newest articles.
* @maintainer nyutag
*/
class ReporterreBridge extends BridgeAbstract{
   
        public function collectData(array $param){

		function ExtractContentReporterre($url) {
		$html2 = file_get_html($url);
		foreach($html2->find('div[style=text-align:justify]') as $e) {
		 $text = $e->outertext;
		}
		$html2->clear();
		unset ($html2);
		return $text;
		}

		$html = file_get_html('http://www.reporterre.net/spip.php?page=backend') or $this->returnError('Could not request Reporterre.', 404);
		$limit = 0;

		foreach($html->find('item') as $element) {
		 if($limit < 5) {
		  $item = new \Item();
		  $item->title = html_entity_decode($element->find('title', 0)->plaintext);
		  $item->timestamp = strtotime($element->find('dc:date', 0)->plaintext);
		  $item->uri = $element->find('guid', 0)->innertext;
		  $item->content = html_entity_decode(ExtractContentReporterre($item->uri));
		  $this->items[] = $item;
		  $limit++;
		 }
		}
    
    }

    public function getName(){
        return 'Reporterre Bridge';
    }

    public function getURI(){
        return 'http://www.reporterre.net/';
    }

    public function getCacheDuration(){
        return 3600; // 1 hours
//	return 0;
    }
}
