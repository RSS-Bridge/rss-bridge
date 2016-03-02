<?php
class CourrierInternationalBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "teromene";
		$this->name = "CourrierInternational";
		$this->uri = "http://CourrierInternational.fr/";
		$this->description = "Courrier International bridge";
		$this->update = "01/09/2015";

	}

    public function collectData(array $param){
	
        $html = '';

        $html = file_get_html('http://www.courrierinternational.com/') or $this->returnError('Error.', 500);
	

	
        $element = $html->find("article");

        $article_count = 1;	

        foreach($element as $article) {
		
            $item = new \Item();
		
            $item->uri = $article->parent->getAttribute("href");

            if(strpos($item->uri, "http") === FALSE) {
                $item->uri = "http://courrierinternational.fr/".$item->uri;
            }
        
            $page = file_get_html($item->uri);

            $cleaner = new HTMLSanitizer();
        
            $item->content = $cleaner->sanitize($page->find("div.article-text")[0]);
            $item->title = strip_tags($article->find(".title")[0]);

            $dateTime = date_parse($page->find("time")[0]);

            $item->timestamp = mktime(
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

    public function getName(){
        return 'Courrier International Bridge';
    }

    public function getURI(){
        return 'http://courrierinternational.com';
    }

    public function getCacheDuration(){
        return 300; // 5 minutes
    }
}

?>
