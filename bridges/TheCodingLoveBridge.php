<?php
class TheCodingLoveBridge extends BridgeAbstract{

	public $maintainer = "superbaillot.net";
	public $name = "The Coding Love";
	public $uri = "http://thecodinglove.com/";
	public $description = "The Coding Love";

    public function collectData(){
        $html = $this->getSimpleHTMLDOM('http://thecodinglove.com/') or $this->returnServerError('Could not request The Coding Love.');

        foreach($html->find('div.post') as $element) {
            $item = array();
            $temp = $element->find('h3 a', 0);

            $titre = $temp->innertext;
            $url = $temp->href;

            $temp = $element->find('div.bodytype', 0);

            // retrieve .gif instead of static .jpg
            $images = $temp->find('p.e img');
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
                $item['author'] = $auteur;
            }


            $item['content'] .= trim($content);
            $item['uri'] = $url;
            $item['title'] = trim($titre);

            $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 7200; // 2h hours
    }
}
