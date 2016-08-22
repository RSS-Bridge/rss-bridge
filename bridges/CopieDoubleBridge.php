<?php
class CopieDoubleBridge extends BridgeAbstract{

	public function loadMetadatas() {

		$this->maintainer = "superbaillot.net";
		$this->name = "CopieDouble";
		$this->uri = "http://www.copie-double.com/";
		$this->description = "CopieDouble";

	}


    public function collectData(array $param){
        $html = $this->getSimpleHTMLDOM('http://www.copie-double.com/') or $this->returnServerError('Could not request CopieDouble.');
        $table = $html->find('table table', 2);

        foreach($table->find('tr') as $element)
        {
            $td = $element->find('td', 0);
             $cpt++;
            if($td->class == "couleur_1")
            {
                $item = array();

                $title = $td->innertext;
                $pos = strpos($title, "<a");
                $title = substr($title, 0, $pos);
                $item['title'] = $title;
            }
            elseif(strpos($element->innertext, "/images/suivant.gif") === false)
            {
                $a=$element->find("a", 0);
                $item['uri'] = "http://www.copie-double.com" . $a->href;

                $content = str_replace('src="/', 'src="http://www.copie-double.com/',$element->find("td", 0)->innertext);
                $content = str_replace('href="/', 'href="http://www.copie-double.com/',$content);
                $item['content'] = $content;
                $this->items[] = $item;
            }
        }
    }

    public function getCacheDuration(){
        return 14400; // 4 hours
    }
}
