<?php
class GizmodoFRBridge extends BridgeAbstract{

	const MAINTAINER = "polopollo";
	const NAME = "GizmodoFR";
	const URI = "http://www.gizmodo.fr/";
	const DESCRIPTION = "Returns the 15 newest posts from GizmodoFR (full text).";

    public function collectData(){

        function GizmodoFRExtractContent($url) {
            $articleHTMLContent = $this->getSimpleHTMLDOM($url);
            if(!$articleHTMLContent){
              return 'Could not load '.$url;
            }
            $text = $articleHTMLContent->find('div.entry-thumbnail', 0)->innertext;
            $text = $text.$articleHTMLContent->find('div.entry-excerpt', 0)->innertext;
            $text = $text.$articleHTMLContent->find('div.entry-content', 0)->innertext;
            foreach($articleHTMLContent->find('pagespeed_iframe') as $element) {
                $text = $text.'<p>link to a iframe (could be a video): <a href="'.$element->src.'">'.$element->src.'</a></p><br>';
            }

            $text = strip_tags($text, '<p><b><a><blockquote><img><em>');
            return $text;
        }

        $rssFeed = $this->getSimpleHTMLDOM(self::URI.'/feed')
          or $this->returnServerError('Could not request '.self::URI.'/feed');
    	$limit = 0;

    	foreach($rssFeed->find('item') as $element) {
            if($limit < 15) {
                $item = array();
                $item['title'] = $element->find('title', 0)->innertext;
                $item['uri'] = $element->find('guid', 0)->plaintext;
                $item['timestamp'] = strtotime($element->find('pubDate', 0)->plaintext);
                $item['content'] = GizmodoFRExtractContent($item['uri']);
                $this->items[] = $item;
                $limit++;
            }
    	}

    }

    public function getCacheDuration(){
        return 1800; // 30min
    }
}
