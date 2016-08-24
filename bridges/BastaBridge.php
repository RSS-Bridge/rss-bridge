<?php
class BastaBridge extends BridgeAbstract{
	public function loadMetadatas() {
		$this->maintainer = "qwertygc";
		$this->name = "Bastamag Bridge";
		$this->uri = "http://www.bastamag.net/";
		$this->description = "Returns the newest articles.";
	}

	public function collectData(){
		// Replaces all relative image URLs by absolute URLs. Relative URLs always start with 'local/'!
		function ReplaceImageUrl($content){
			return preg_replace('/src=["\']{1}([^"\']+)/ims', 'src=\'http://www.bastamag.net/$1\'', $content);
		}

		$html = $this->getSimpleHTMLDOM('http://www.bastamag.net/spip.php?page=backend') or $this->returnServerError('Could not request Bastamag.');
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 10) {
				$item = array();
				$item['title'] = $element->find('title', 0)->innertext;
				$item['uri'] = $element->find('guid', 0)->plaintext;
				$item['timestamp'] = strtotime($element->find('dc:date', 0)->plaintext);
				$item['content'] = ReplaceImageUrl($this->getSimpleHTMLDOM($item['uri'])->find('div.texte', 0)->innertext);
				$this->items[] = $item;
				$limit++;
			}
		}
	}

	public function getCacheDuration(){
		return 3600*2; // 2 hours
	}
}
?>
