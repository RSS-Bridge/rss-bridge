<?php
class TheCodingLoveBridge extends BridgeAbstract{

	const MAINTAINER = "superbaillot.net";
	const NAME = "The Coding Love";
	const URI = "http://thecodinglove.com/";
	const CACHE_TIMEOUT = 7200; // 2h
	const DESCRIPTION = "The Coding Love";

    public function collectData(){
      $html = getSimpleHTMLDOM(self::URI)
        or returnServerError('Could not request The Coding Love.');

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
}
