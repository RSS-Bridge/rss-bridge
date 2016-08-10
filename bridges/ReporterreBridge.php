<?php
class ReporterreBridge extends BridgeAbstract{

		public function loadMetadatas() {
			$this->maintainer = "nyutag";
			$this->name = "Reporterre Bridge";
			$this->uri = "http://www.reporterre.net/";
			$this->description = "Returns the newest articles.";
			$this->update = "2016-08-09";
		}

		private function ExtractContentReporterre($url) {
			$html2 = $this->file_get_html($url);

			foreach($html2->find('div[style=text-align:justify]') as $e) {
				$text = $e->outertext;
			}

			$html2->clear();
			unset ($html2);

			// Replace all relative urls with absolute ones
			$text = preg_replace('/(href|src)(\=[\"\'])(?!http)([^"\']+)/ims', "$1$2" . $this->uri . "$3", $text);

			$text = strip_tags($text, '<p><br><a><img>');
			return $text;
		}

	public function collectData(array $param){
		$html = $this->file_get_html('http://www.reporterre.net/spip.php?page=backend') or $this->returnError('Could not request Reporterre.', 404);
		$limit = 0;

		foreach($html->find('item') as $element) {
			if($limit < 5) {
				$item = new \Item();
				$item->title = html_entity_decode($element->find('title', 0)->plaintext);
				$item->timestamp = strtotime($element->find('dc:date', 0)->plaintext);
				$item->uri = $element->find('guid', 0)->innertext;
				$item->content = html_entity_decode($this->ExtractContentReporterre($item->uri));
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
