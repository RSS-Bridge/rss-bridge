<?php
class MsnMondeBridge extends BridgeAbstract{

	public $maintainer = "kranack";
	public $name = 'MSN Actu Monde';
	public $uri = 'http://www.msn.com/fr-fr/actualite/monde';
	public $description = "Returns the 10 newest posts from MSN Actualités (full text)";

	private function MsnMondeExtractContent($url, &$item) {
		$html2 = $this->getSimpleHTMLDOM($url);
		$item['content'] = $html2->find('#content', 0)->find('article', 0)->find('section', 0)->plaintext;
		$item['timestamp'] = strtotime($html2->find('.authorinfo-txt', 0)->find('time', 0)->datetime);
	}

	public function collectData(){
		$html = $this->getSimpleHTMLDOM($this->uri) or $this->returnServerError('Could not request MsnMonde.');
		$limit = 0;
		foreach($html->find('.smalla') as $article) {
			if($limit < 10) {
				$item = array();
				$item['title'] = utf8_decode($article->find('h4', 0)->innertext);
				$item['uri'] = "http://www.msn.com" . utf8_decode($article->find('a', 0)->href);
				$this->MsnMondeExtractContent($item['uri'], $item);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
