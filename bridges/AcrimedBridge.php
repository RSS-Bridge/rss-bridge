<?php
class AcrimedBridge extends RssExpander{

		public function loadMetadatas() {

			$this->maintainer = "qwertygc";
			$this->name = "Acrimed Bridge";
			$this->uri = "http://www.acrimed.org/";
			$this->description = "Returns the newest articles.";
			$this->update = "2014-05-25";

		}

       public function collectData(array $param){

			parent::collectExpandableDatas($param, "http://www.acrimed.org/spip.php?page=backend");

		}
    
		protected function parseRSSItem($newsItem) {

			$hs = new HTMLSanitizer();

			$namespaces = $newsItem->getNameSpaces(true);
			$dc = $newsItem->children($namespaces['dc']);

			$item = new Item();
			$item->uri = trim($newsItem->link);
        	$item->title = trim($newsItem->title);
        	$item->timestamp = strtotime($dc->date);

			$articlePage = file_get_html($newsItem->link);
			$article = $hs->sanitize($articlePage->find('article.article1', 0)->innertext);
			$article = HTMLSanitizer::defaultImageSrcTo($article, "http://www.acrimed.org/");

			$item->content = $article;


			return $item;

		}

	public function getName() {

		return "Acrimed Bridge";

	}

	public function getURI() {

		return "http://www.acrimed.org/";

	}

    public function getCacheDuration(){
        return 4800; // 2 hours
    }
}
