<?php
class AcrimedBridge extends RssExpander{


	public $maintainer = "qwertygc";
	public $name = "Acrimed Bridge";
	public $uri = "http://www.acrimed.org/";
	public $description = "Returns the newest articles.";

       public function collectData(){

			$this->collectExpandableDatas("http://www.acrimed.org/spip.php?page=backend");

		}

		protected function parseRSSItem($newsItem) {

			$hs = new HTMLSanitizer();

			$namespaces = $newsItem->getNameSpaces(true);
			$dc = $newsItem->children($namespaces['dc']);

			$item = array();
			$item['uri'] = trim($newsItem->link);
        	$item['title'] = trim($newsItem->title);
        	$item['timestamp'] = strtotime($dc->date);

			$articlePage = $this->getSimpleHTMLDOM($newsItem->link);
			$article = $hs->sanitize($articlePage->find('article.article1', 0)->innertext);
			$article = HTMLSanitizer::defaultImageSrcTo($article, "http://www.acrimed.org/");

			$item['content'] = $article;


			return $item;

		}

    public function getCacheDuration(){
        return 4800; // 2 hours
    }
}
