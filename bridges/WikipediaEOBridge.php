<?php
/**
* RssBridgeWikipediaEO
* Retrieve latest highlighted articles from Wikipedia in Esperanto.
*
* @name Wikipedia EO "Artikolo de la semajno"
* @description Returns the highlighted eo.wikipedia.org article.
*/
class WikipediaEOBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';
        $host = 'http://eo.wikipedia.org';
        // If you want HTTPS access instead, uncomment the following line:
        //$host = 'https://eo.wikipedia.org';
        $link = '/wiki/Vikipedio:%C4%88efpa%C4%9Do';

        $html = file_get_html($host.$link) or $this->returnError('Could not request Wikipedia EO.', 404);

		$element = $html->find('div[id=mf-tfa]', 0);
		// Link to article
		$link = $element->find('p', -2)->find('a', 0);
		$item = new \Item();
		$item->uri = $host.$link->href;
		$item->title = $link->title;
		$item->content = str_replace('href="/', 'href="'.$host.'/', $element->innertext);
		$this->items[] = $item;
    }

    public function getName(){
        return 'Wikipedia EO "Artikolo de la semajno"';
    }

    public function getURI(){
        return 'https://eo.wikipedia.org/wiki/Vikipedio:%C4%88efpa%C4%9Do';
    }

    public function getCacheDuration(){
        return 3600*12; // 12 hours
    }
}
