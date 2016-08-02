<?php
class CoinDeskBridge extends BridgeAbstract {

	public function loadMetadatas() {

		$this->maintainer = "mitsukarenai";
		$this->name = "CoinDesk";
		$this->uri = "http://www.coindesk.com/";
		$this->description = "Returns the 5 newest posts from CoinDesk (full text)";
		$this->update = "2014-05-30";

	}

    public function collectData(array $param) {

    function CoinDeskStripCDATA($string) {
    	$string = str_replace('<![CDATA[', '', $string);
    	$string = str_replace(']]>', '', $string);
    	return $string;
    }
    function CoinDeskExtractContent($url) {
		//FIXME: We need to change the $this->file_get_html to a static
		$html2 = file_get_html($url);
		$text = $html2->find('div.single-content', 0)->innertext;
		$text = strip_tags($text, '<p><a><img>');
		return $text;
    }

    $html = $this->file_get_html('http://www.coindesk.com/feed/atom/') or $this->returnError('Could not request CoinDesk.', 404);
	$limit = 0;

	foreach($html->find('entry') as $element) {
	 if($limit < 5) {
	 $item = new \Item();
	 $item->title = CoinDeskStripCDATA($element->find('title', 0)->innertext);
	 $item->author = $element->find('author', 0)->plaintext;
	 $item->uri = $element->find('link', 0)->href;
	 $item->timestamp = strtotime($element->find('published', 0)->plaintext);
	 $item->content = CoinDeskExtractContent($item->uri);
	 $this->items[] = $item;
	 $limit++;
	 }
	}
    
    }

    public function getName(){
        return 'CoinDesk';
    }

    public function getURI(){
        return 'http://www.coindesk.com/';
    }

    public function getCacheDuration(){
        return 1800; // 30min
    }
}
