<?php
class GizmodoFRBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "polopollo";
		$this->name = "GizmodoFR";
		$this->uri = "http://www.gizmodo.fr/";
		$this->description = "Returns the 15 newest posts from GizmodoFR (full text).";

	}

    public function collectData(array $param){

        function GizmodoFRExtractContent($url) {
            $articleHTMLContent = $this->getSimpleHTMLDOM($url);
            $text = $articleHTMLContent->find('div.entry-thumbnail', 0)->innertext;
            $text = $text.$articleHTMLContent->find('div.entry-excerpt', 0)->innertext;
            $text = $text.$articleHTMLContent->find('div.entry-content', 0)->innertext;
            foreach($articleHTMLContent->find('pagespeed_iframe') as $element) {
                $text = $text.'<p>link to a iframe (could be a video): <a href="'.$element->src.'">'.$element->src.'</a></p><br>';
            }

            $text = strip_tags($text, '<p><b><a><blockquote><img><em>');
            return $text;
        }

        $rssFeed = $this->getSimpleHTMLDOM('http://www.gizmodo.fr/feed') or $this->returnServerError('Could not request http://www.gizmodo.fr/feed');
    	$limit = 0;

    	foreach($rssFeed->find('item') as $element) {
            if($limit < 15) {
                $item = new \Item();
                $item->title = $element->find('title', 0)->innertext;
                $item->uri = $element->find('guid', 0)->plaintext;
                $item->timestamp = strtotime($element->find('pubDate', 0)->plaintext);
                $item->content = GizmodoFRExtractContent($item->uri);
                $this->items[] = $item;
                $limit++;
            }
    	}

    }

    public function getCacheDuration(){
        return 1800; // 30min
    }
}
