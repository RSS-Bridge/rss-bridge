<?php
/**
* RssBridgeCAD
* Returns the newest articles
* 2015-04-03
*
* @name CAD Bridge
* @homepage http://www.cad-comic.com/
* @description Returns the newest articles.
* @maintainer nyutag
*/
class CADBridge extends BridgeAbstract{
   
        public function collectData(array $param){

		function CADUrl($string) {
		 $html2 = explode("\"", $string);
		 $string = $html2[1];
		 if (substr($string,0,4) != 'http')
		   return 'notanurl';
		 return $string;
		}
	
		function CADExtractContent($url) {
		$html3 = file_get_html($url);
		preg_match_all("/http:\/\/cdn2\.cad-comic\.com\/comics\/cad-\S*png/", $html3, $url2);
		$img = implode ($url2[0]);
		return $img;
		}

		$html = file_get_html('http://cdn2.cad-comic.com/rss.xml') or $this->returnError('Could not request CAD.', 404);
		$limit = 0;
		foreach($html->find('item') as $element) {
		 if($limit < 3) {
		 $item = new \Item();
		 $item->title = $element->find('title', 0)->innertext;
		 $item->uri = CADUrl($element->find('description', 0)->innertext);
		 if ($item->uri != 'notanurl') {
		   $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
		   $item->content = '<img src="'.CADExtractContent($item->uri).'"/>';
		   $this->items[] = $item;
		   $limit++;
		  }
		 }
		}
    
    }

    public function getName(){
        return 'CAD Bridge';
    }

    public function getURI(){
        return 'http://www.cad-comic.com/';
    }

    public function getCacheDuration(){
        return 3600*2; // 2 hours
//	return 0;
    }
}
