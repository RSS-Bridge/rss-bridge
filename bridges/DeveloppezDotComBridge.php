<?php
/**
* RssBridgeDeveloppezDotCom
* Returns the 15 newest posts from http://www.developpez.com (full text)
* 2014-07-14
*
* @name Developpez.com Actus (FR)
* @homepage http://www.developpez.com/
* @description Returns the 15 newest posts from DeveloppezDotCom (full text).
* @maintainer polopollo
*/
class DeveloppezDotComBridge extends BridgeAbstract{

    public function collectData(array $param){

        function DeveloppezDotComStripCDATA($string) {
            $string = str_replace('<![CDATA[', '', $string);
            $string = str_replace(']]>', '', $string);
            return $string;
        }

        function DeveloppezDotComExtractContent($url) {
            $articleHTMLContent = file_get_html($url);
            $text = $text.$articleHTMLContent->find('div.content', 0)->innertext;
            $text = strip_tags($text, '<p><b><a><blockquote><img><em><br/><br><ul><li>');
            return $text;
        }

        $rssFeed = file_get_html('http://www.developpez.com/index/rss') or $this->returnError('Could not request http://www.developpez.com/index/rss', 404);
    	$limit = 0;

    	foreach($rssFeed->find('item') as $element) {
            if($limit < 15) {
                $item = new \Item();
                $item->title = DeveloppezDotComStripCDATA($element->find('title', 0)->innertext);
                $item->uri = DeveloppezDotComStripCDATA($element->find('guid', 0)->plaintext);
                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                $item->content = DeveloppezDotComExtractContent($item->uri);
                $this->items[] = $item;
                $limit++;
            }
    	}

    }

    public function getName(){
        return 'DeveloppezDotCom';
    }

    public function getURI(){
        return 'http://www.developpez.com/';
    }

    public function getCacheDuration(){
        return 1800; // 30min
    }
}
