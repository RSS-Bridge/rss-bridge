<?php
class AllocineTueursEnSerieBridge extends BridgeAbstract{

    private $_URL = "http://www.allocine.fr/video/programme-12286/saison-22938/";
    private $_NOM = "Tueurs en Séries";

	public function loadMetadatas() {

		$this->maintainer = "superbaillot.net";
		$this->name = "Allo Cine : Tueurs En Serie";
		$this->uri = "http://www.allocine.fr/video/programme-12286/saison-22938/";
		$this->description = "Allo Cine : Tueurs En Serie";
		$this->update = "2016-08-06";

	}

    public function collectData(array $param){
        $html = $this->file_get_html($this->_URL) or $this->returnError('Could not request Allo cine.', 404);

        foreach($html->find('figure.media-meta-fig') as $element)
        {
            $item = new Item();
            
            $titre = $element->find('div.titlebar h3.title a', 0);
            $content = trim($element->innertext);
            
            $figCaption = strpos($content, $this->_NOM);
            if($figCaption !== false)
            {
                $content = str_replace('src="/', 'src="http://www.allocine.fr/',$content);
                $content = str_replace('href="/', 'href="http://www.allocine.fr/',$content);
                $content = str_replace('src=\'/', 'src=\'http://www.allocine.fr/',$content);
                $content = str_replace('href=\'/', 'href=\'http://www.allocine.fr/',$content);
                $item->content = $content;
                $item->title = trim($titre->innertext);
                $item->uri = "http://www.allocine.fr" . $titre->href;
                $this->items[] = $item;
            }
        }
    }

    public function getName(){
        return 'Allo Cine : ' . $this->_NOM;
    }

    public function getURI(){
        return $this->_URL;
    }

    public function getCacheDuration(){
        return 25200; // 7 hours
    }
}
?>
