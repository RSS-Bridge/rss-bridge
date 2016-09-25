<?php
class MsnMondeBridge extends BridgeAbstract{

	const MAINTAINER = "kranack";
	const NAME = 'MSN Actu Monde';
	const URI = 'http://www.msn.com/';
	const DESCRIPTION = "Returns the 10 newest posts from MSN Actualités (full text)";

    public function getURI(){
        return self::URI.'fr-fr/actualite/monde';
    }

	private function MsnMondeExtractContent($url, &$item) {
		$html2 = getSimpleHTMLDOM($url);
		$item['content'] = $html2->find('#content', 0)->find('article', 0)->find('section', 0)->plaintext;
		$item['timestamp'] = strtotime($html2->find('.authorinfo-txt', 0)->find('time', 0)->datetime);
	}

	public function collectData(){
		$html = getSimpleHTMLDOM($this->getURI()) or returnServerError('Could not request MsnMonde.');
		$limit = 0;
		foreach($html->find('.smalla') as $article) {
			if($limit < 10) {
				$item = array();
				$item['title'] = utf8_decode($article->find('h4', 0)->innertext);
				$item['uri'] = self::URI . utf8_decode($article->find('a', 0)->href);
				$this->MsnMondeExtractContent($item['uri'], $item);
				$this->items[] = $item;
				$limit++;
			}
		}
	}
}
