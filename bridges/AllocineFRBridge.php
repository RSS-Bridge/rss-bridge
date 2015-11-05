<?php
class AllocineFRBridge extends BridgeAbstract{

    private $_URL = "http://www.allocine.fr/video/programme-12284/saison-24580/";
    private $_NOM = "Faux Raccord";

    public function loadMetadatas() {

		$this->maintainer = "superbaillot.net";
		$this->name = "Allo Cine : Faux Raccord";
		$this->uri = "http://www.allocine.fr/video/programme-12284/saison-24580/";
		$this->description = "Allo Cine : Faux Raccord";
		$this->update = "07/11/2013";

	}

    public function collectData(array $param){
        $html = file_get_html($this->_URL) or $this->returnError('Could not request Allo cine.', 404);

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
    public function getDescription(){
        return "Allo Cine : " . $this->_NOM . " via rss-bridge";
    }
}
?>
