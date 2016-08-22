<?php
class CourrierInternationalBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "teromene";
		$this->name = "Courrier International Bridge";
		$this->uri = "http://CourrierInternational.fr/";
		$this->description = "Courrier International bridge";

	}

    public function collectData(array $param){

        $html = '';

        $html = $this->getSimpleHTMLDOM('http://www.courrierinternational.com/') or $this->returnServerError('Error.');



        $element = $html->find("article");

        $article_count = 1;

        foreach($element as $article) {

            $item = array();

            $item['uri'] = $article->parent->getAttribute("href");

            if(strpos($item['uri'], "http") === FALSE) {
                $item['uri'] = "http://courrierinternational.fr/".$item['uri'];
            }

            $page = $this->getSimpleHTMLDOM($item['uri']);

            $cleaner = new HTMLSanitizer();

            $item['content'] = $cleaner->sanitize($page->find("div.article-text")[0]);
            $item['title'] = strip_tags($article->find(".title")[0]);

            $dateTime = date_parse($page->find("time")[0]);

            $item['timestamp'] = mktime(
       			$dateTime['hour'],
        		$dateTime['minute'],
        		$dateTime['second'],
        		$dateTime['month'],
        		$dateTime['day'],
        		$dateTime['year']
            );

            $this->items[] = $item;
            $article_count ++;
            if($article_count > 5) break;

        }



    }

    public function getCacheDuration(){
        return 300; // 5 minutes
    }
}

?>
