<?php
class CopieDoubleBridge extends BridgeAbstract{

    public $maintainer = "superbaillot.net";
    public $name = "CopieDouble";
    public $uri = "http://www.copie-double.com/";
    public $description = "CopieDouble";

    public function collectData(){
        $html = $this->getSimpleHTMLDOM($this->uri)
            or $this->returnServerError('Could not request CopieDouble.');
        $table = $html->find('table table', 2);

        foreach($table->find('tr') as $element)
        {
            $td = $element->find('td', 0);
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
                $item['uri'] = $this->uri . $a->href;

                $content = str_replace('src="/', 'src="/'.$this->uri,$element->find("td", 0)->innertext);
                $content = str_replace('href="/', 'href="'.$this->uri,$content);
                $item['content'] = $content;
                $this->items[] = $item;
            }
        }
    }

    public function getCacheDuration(){
        return 14400; // 4 hours
    }
}
