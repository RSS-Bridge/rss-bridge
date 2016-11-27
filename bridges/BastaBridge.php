<?php
class BastaBridge extends BridgeAbstract{
	const MAINTAINER = "qwertygc";
	const NAME = "Bastamag Bridge";
	const URI = "http://www.bastamag.net/";
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = "Returns the newest articles.";

	public function collectData(){
		// Replaces all relative image URLs by absolute URLs. Relative URLs always start with 'local/'!
		function ReplaceImageUrl($content){
			return preg_replace('/src=["\']{1}([^"\']+)/ims', 'src=\''.self::URI.'$1\'', $content);
		}

        $html = getSimpleHTMLDOM(self::URI.'spip.php?page=backend')
            or returnServerError('Could not request Bastamag.');
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 10) {
				$item = array();
				$item['title'] = $element->find('title', 0)->innertext;
				$item['uri'] = $element->find('guid', 0)->plaintext;
				$item['timestamp'] = strtotime($element->find('dc:date', 0)->plaintext);
				$item['content'] = ReplaceImageUrl(getSimpleHTMLDOM($item['uri'])->find('div.texte', 0)->innertext);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
?>
