<?php
/**
* RssBridgeNextinpact
* Returns the newest articles
*
* @name Nextinpact Bridge
* @description Returns the newest articles.
*/
class NextInpactBridge extends BridgeAbstract{
    
        public function collectData(array $param){

			function StripCDATA($string) {
			$string = str_replace('<![CDATA[', '', $string);
			$string = str_replace(']]>', '', $string);
			return $string;
		}
		function ExtractContent($url) {
		$html2 = file_get_html($url);
		$text = $html2->find('div#actu_content', 0)->innertext;
		return $text;
		}
		$html = file_get_html('http://www.nextinpact.com/rss/news.xml') or $this->returnError('Could not request Nextinpact.', 404);
		$limit = 0;

		foreach($html->find('item') as $element) {
		 if($limit < 10) {
		 $item = new \Item();
		 $item->title = StripCDATA($element->find('title', 0)->innertext);
		 $item->uri = StripCDATA($element->find('guid', 0)->plaintext);
		 $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
		 $item->content = ExtractContent($item->uri);
		 $this->items[] = $item;
		 $limit++;
		 }
		}
    
    }

    public function getName(){
        return 'Nextinpact Bridge';
    }

    public function getURI(){
        return 'http://www.nextinpact.com/';
    }

    public function getCacheDuration(){
        return 3600*2; // 2 hours
		// return 0;
    }
}
