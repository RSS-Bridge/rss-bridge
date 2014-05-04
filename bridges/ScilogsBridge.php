<?php
/**
* RssBridgeScilogs
* Returns the newest articles
*
* @name Scilogs Bridge
* @description Returns the newest articles.
*/
class ScilogsBridge extends BridgeAbstract{
    
        public function collectData(array $param){

			function ScilogsStripCDATA($string) {
			$string = str_replace('<![CDATA[', '', $string);
			$string = str_replace(']]>', '', $string);
			return $string;
		}
		function ScilogsExtractContent($url) {
		$html2 = file_get_html($url);
		$text = $html2->find('div.entrybody', 0)->innertext;
		return $text;
		}
		$html = file_get_html('http://www.scilogs.fr/?wpmu-feed=posts') or $this->returnError('Could not request Scilogs.', 404);
		$limit = 0;

		foreach($html->find('item') as $element) {
		 if($limit < 10) {
		 $item = new \Item();
		 $item->title = ScilogsStripCDATA($element->find('title', 0)->innertext);
		 $item->uri = ScilogsStripCDATA($element->find('guid', 0)->plaintext);
		 $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
		 $item->content = ScilogsExtractContent($item->uri);
		 $this->items[] = $item;
		 $limit++;
		 }
		}
    
    }

    public function getName(){
        return 'Scilogs Bridge';
    }

    public function getURI(){
        return 'http://bastamag.net/';
    }

    public function getCacheDuration(){
        return 3600*2; // 2 hours
    }
}
