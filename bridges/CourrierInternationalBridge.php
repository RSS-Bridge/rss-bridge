<?php
class CourrierInternationalBridge extends BridgeAbstract{

    const MAINTAINER = "teromene";
    const NAME = "Courrier International Bridge";
    const URI = "http://CourrierInternational.com/";
    const DESCRIPTION = "Courrier International bridge";

    public function collectData(){

        $html = $this->getSimpleHTMLDOM(self::URI)
            or $this->returnServerError('Error.');

        $element = $html->find("article");

        $article_count = 1;

        foreach($element as $article) {

            $item = array();

            $item['uri'] = $article->parent->getAttribute("href");

            if(strpos($item['uri'], "http") === FALSE) {
                $item['uri'] = self::URI.$item['uri'];
            }


            $page = $this->getSimpleHTMLDOMCached($item['uri']);

            $cleaner = new HTMLSanitizer();

            $content = $page->find('.article-text',0);
            if(!$content){
              $content = $page->find('.depeche-text',0);
            }

            $item['content'] = $cleaner->sanitize($content);
            $item['title'] = strip_tags($article->find(".title",0));

            $dateTime = date_parse($page->find("time",0));

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
