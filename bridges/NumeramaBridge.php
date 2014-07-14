<?php
/**
* RssBridgeNumerama
* Returns the 5 newest posts from http://www.numerama.com (full text)
* 2014-05-25
*
* @name Numerama
* @homepage http://www.numerama.com/
* @description Returns the 5 newest posts from Numerama (full text)
* @maintainer mitsukarenai
*/
class NumeramaBridge extends BridgeAbstract{

    public function collectData(array $param){

    function NumeramaStripCDATA($string) {
    	$string = str_replace('<![CDATA[', '', $string);
    	$string = str_replace(']]>', '', $string);
    	return $string;
    }
    function NumeramaExtractContent($url) {
	$html2 = file_get_html($url);
	$text = $html2->find('h2.intro', 0)->innertext;
	$text = $text.$html2->find('div.content', 0)->innertext;
	$text = strip_tags($text, '<p><b><a><blockquote><img><em>');
	return $text;
    }
        $html = file_get_html('http://www.numerama.com/rss/news.rss') or $this->returnError('Could not request Numerama.', 404);
	$limit = 0;

	foreach($html->find('item') as $element) {
	 if($limit < 5) {
	 $item = new \Item();
	 $item->title = NumeramaStripCDATA($element->find('title', 0)->innertext);
	 $item->uri = NumeramaStripCDATA($element->find('guid', 0)->plaintext);
	 $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
	 $item->content = NumeramaExtractContent($item->uri);
	 $this->items[] = $item;
	 $limit++;
	 }
	}

    }

    public function getName(){
        return 'Numerama';
    }

    public function getURI(){
        return 'http://www.numerama.com/';
    }

    public function getCacheDuration(){
        return 1800; // 30min
    }
}
