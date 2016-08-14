<?php
class MsnMondeBridge extends BridgeAbstract{

	public function loadMetadatas() {
		$this->maintainer = "kranack";
		$this->name = 'MSN Actu Monde';
		$this->uri = 'http://www.msn.com/fr-fr/actualite/monde';
		$this->description = "Returns the 10 newest posts from MSN Actualités (full text)";
		$this->update = "2016-08-09";
	}

	private function MsnMondeExtractContent($url, &$item) {
		$html2 = $this->file_get_html($url);
		$item->content = $html2->find('#content', 0)->find('article', 0)->find('section', 0)->plaintext;
		$item->timestamp = strtotime($html2->find('.authorinfo-txt', 0)->find('time', 0)->datetime);
	}

	public function collectData(array $param){
		$html = $this->file_get_html($this->uri) or $this->returnError('Could not request MsnMonde.', 404);
		$limit = 0;
		foreach($html->find('.smalla') as $article) {
			if($limit < 10) {
				$item = new \Item();
				$item->title = utf8_decode($article->find('h4', 0)->innertext);
				$item->uri = "http://www.msn.com" . utf8_decode($article->find('a', 0)->href);
				$this->MsnMondeExtractContent($item->uri, $item);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
