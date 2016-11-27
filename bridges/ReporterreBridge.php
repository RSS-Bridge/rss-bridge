<?php
class ReporterreBridge extends BridgeAbstract{

		const MAINTAINER = "nyutag";
		const NAME = "Reporterre Bridge";
		const URI = "http://www.reporterre.net/";
		const DESCRIPTION = "Returns the newest articles.";

		private function ExtractContentReporterre($url) {
			$html2 = getSimpleHTMLDOM($url);

			foreach($html2->find('div[style=text-align:justify]') as $e) {
				$text = $e->outertext;
			}

			$html2->clear();
			unset ($html2);

			// Replace all relative urls with absolute ones
			$text = preg_replace('/(href|src)(\=[\"\'])(?!http)([^"\']+)/ims', "$1$2" . self::URI . "$3", $text);

			$text = strip_tags($text, '<p><br><a><img>');
			return $text;
		}

	public function collectData(){
		$html = getSimpleHTMLDOM(self::URI.'spip.php?page=backend') or returnServerError('Could not request Reporterre.');
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 5) {
				$item = array();
				$item['title'] = html_entity_decode($element->find('title', 0)->plaintext);
				$item['timestamp'] = strtotime($element->find('dc:date', 0)->plaintext);
				$item['uri'] = $element->find('guid', 0)->innertext;
				$item['content'] = html_entity_decode($this->ExtractContentReporterre($item['uri']));
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
