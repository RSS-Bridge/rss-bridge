<?php
class LesJoiesDuCodeBridge extends BridgeAbstract{

	const MAINTAINER = "superbaillot.net";
	const NAME = "Les Joies Du Code";
	const URI = "http://lesjoiesducode.fr/";
	const DESCRIPTION = "LesJoiesDuCode";

    public function collectData(){
        $html = $this->getSimpleHTMLDOM(self::URI)
            or $this->returnServerError('Could not request LesJoiesDuCode.');

        foreach($html->find('div.blog-post') as $element) {
            $item = array();
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
