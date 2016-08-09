<?php
class LesJoiesDuCodeBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "superbaillot.net";
		$this->name = "Les Joies Du Code";
		$this->uri = "http://lesjoiesducode.fr/";
		$this->description = "LesJoiesDuCode";
		$this->update = "2016-08-09";

	}

    public function collectData(array $param){
        $html = $this->file_get_html('http://lesjoiesducode.fr/') or $this->returnError('Could not request LesJoiesDuCode.', 404);
    
        foreach($html->find('div.blog-post') as $element) {
            $item = new Item();
            $temp = $element->find('h1 a', 0);
            $titre = html_entity_decode($temp->innertext);
            $url = $temp->href;
            
            $temp = $element->find('div.blog-post-content', 0);

            // retrieve .gif instead of static .jpg
            $images = $temp->find('p img');
            foreach($images as $image){
              $img_src = str_replace(".jpg",".gif",$image->src);
              $image->src = $img_src;
            }
            $content = $temp->innertext;
            
            $auteur = $temp->find('i', 0);
            $pos = strpos($auteur->innertext, "by");
            
            if($pos > 0)
            {
                $auteur = trim(str_replace("*/", "", substr($auteur->innertext, ($pos + 2))));
                $item->author = $auteur;
            }
            
            
            $item->content .= trim($content);
            $item->uri = $url;
            $item->title = trim($titre);
            
            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
