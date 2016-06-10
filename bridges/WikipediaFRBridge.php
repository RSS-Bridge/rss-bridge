<?php
class WikipediaFRBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "gsurrel";
		$this->name = "Wikipedia FR 'Lumière sur...'";
		$this->uri = "https://fr.wikipedia.org/";
		$this->description = "Returns the highlighted fr.wikipedia.org article.";
		$this->update = "2016-06-04";

	}

    public function collectData(array $param){
        $html = '';
        $host = 'http://fr.wikipedia.org';
        // If you want HTTPS access instead, uncomment the following line:
        //$host = 'https://fr.wikipedia.org';
        $link = '/wiki/Wikip%C3%A9dia:Accueil_principal';

        $html = file_get_html($host.$link) or $this->returnError('Could not request Wikipedia FR.', 404);

		$element = $html->find('div[id=mf-lumieresur]', 0);
		# Use the "Lire la suite" link to dependably get the title of the article
		# usually it's a child of a li.BA element (Bon article)
		# occasionally it's a li.AdQ (Article de qualité)
		$lirelasuite_link = $element->find('.BA > i > a, .AdQ > i > a', 0);
		$item = new \Item();
		$item->uri = $host.$lirelasuite_link->href;
		$item->title = $lirelasuite_link->title;
		$item->content = str_replace('href="/', 'href="'.$host.'/', $element->innertext);
		$this->items[] = $item;
    }

    public function getName(){
        return 'Wikipedia FR "Lumière sur..."';
    }

    public function getURI(){
        return 'https://fr.wikipedia.org/wiki/Wikip%C3%A9dia:Accueil_principal';
    }

    public function getCacheDuration(){
        return 3600*4; // 4 hours
    }
}
