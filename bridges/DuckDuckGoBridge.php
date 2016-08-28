<?php
class DuckDuckGoBridge extends BridgeAbstract{

	public $maintainer = "Astalaseven";
	public $name = "DuckDuckGo";
	public $uri = "https://duckduckgo.com/";
	public $description = "Returns most recent results from DuckDuckGo.";

    public $parameters = array( array(
        'u'=>array(
            'name'=>'keyword',
            'required'=>true)
        ));

    public function collectData(){
        $html = $this->getSimpleHTMLDOM($this->uri.'html/?q='.$this->getInput('u').'+sort:date')
            or $this->returnServerError('Could not request DuckDuckGo.');

        foreach($html->find('div.results_links') as $element) {
                $item = array();
                $item['uri'] = $element->find('a', 0)->href;
                $item['title'] = $element->find('a', 1)->innertext;
                $item['content'] = $element->find('div.snippet', 0)->plaintext;
                $this->items[] = $item;
        }
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
