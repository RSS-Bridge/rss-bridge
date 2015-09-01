<?php
/**
*
* @name CourrierInternational
* @homepage http://CourrierInternational.fr/
* @description Courrier International bridge
* @update 01/09/2015
* @maintainer teromene
*/

class CourrierInternationalBridge extends BridgeAbstract{

    public function collectData(array $param){
	
	function fetchArticle($link) {
		
		$page = file_get_html($link);

		$contenu = $page->find(".article-text")[0];
		
		return strip_tags($contenu);
		


	}

	$html = '';

        $html = file_get_html('http://www.courrierinternational.com/article') or $this->returnError('Error.', 500);
	

	
	$element = $html->find(".type-normal");

	$article_count = 1;	

	foreach($element as $article) {
		
		$item = new \Item();
		
		$item->uri = "http://www.courrierinternational.com".$article->find("a")[0]->getAttribute("href");
		$item->content = fetchArticle("http://www.courrierinternational.com".$article->find("a")[0]->getAttribute("href"));
		$item->title = strip_tags($article->find("h2")[0]);

		$dateTime = date_parse($article->find("time")[0]);

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
