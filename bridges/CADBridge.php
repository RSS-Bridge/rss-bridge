<?php
class CADBridge extends BridgeAbstract{
	public function loadMetadatas() {
		$this->maintainer = "nyutag";
		$this->name = "CAD Bridge";
		$this->uri = "http://www.cad-comic.com/";
		$this->description = "Returns the newest articles.";
		$this->update = "2016-08-09";
	}

	private function CADExtractContent($url) {
		$html3 = $this->file_get_html($url);

		// The request might fail due to missing https support or wrong URL
		if($html3 == false)
			return 'Daily comic not released yet';

		$htmlpart = explode("/", $url);

		switch ($htmlpart[3]){
			case 'cad':
				preg_match_all("/http:\/\/cdn2\.cad-comic\.com\/comics\/cad-\S*png/", $html3, $url2);
				break;
			case 'sillies':
				preg_match_all("/http:\/\/cdn2\.cad-comic\.com\/comics\/sillies-\S*gif/", $html3, $url2);
				break;
			default:
				return 'Daily comic not released yet';
		}

		$img = implode ($url2[0]);
		$html3->clear();
		unset ($html3);
		if ($img == '')
			return 'Daily comic not released yet';
		return '<img src="'.$img.'"/>';
	}

	public function collectData(array $param){
		function CADUrl($string) {
			$html2 = explode("\"", $string);
			$string = $html2[1];
			if (substr($string,0,4) != 'http')
				return 'notanurl';
			return $string;
		}

		$html = $this->file_get_html('http://cdn2.cad-comic.com/rss.xml') or $this->returnError('Could not request CAD.', 404);
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 5) {
				$item = new \Item();
				$item->title = $element->find('title', 0)->innertext;
				$item->uri = CADUrl($element->find('description', 0)->innertext);
				if ($item->uri != 'notanurl') {
					$item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
					$item->content = $this->CADExtractContent($item->uri);
					$this->items[] = $item;
					$limit++;
				}
			}
		}
	}

	public function getCacheDuration(){
		return 3600*2; // 2 hours
	}
}
?>