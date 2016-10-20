<?php
class DuckDuckGoBridge extends BridgeAbstract{

	const MAINTAINER = "Astalaseven";
	const NAME = "DuckDuckGo";
	const URI = "https://duckduckgo.com/";
	const CACHE_TIMEOUT = 21600; // 6h
	const DESCRIPTION = "Returns results from DuckDuckGo.";

	const SORT_DATE = '+sort:date';
	const SORT_RELEVANCE = '';

    const PARAMETERS = array( array(
        'u'=>array(
            'name'=>'keyword',
            'required'=>true),
        'sort'=>array(
            'name'=>'sort by',
            'type'=>'list',
            'required'=>false,
            'values'=>array(
                'date'=>self::SORT_DATE,
                'relevance'=>self::SORT_RELEVANCE
                ),
            'defaultValue'=>self::SORT_DATE
            )
        ));

    public function collectData(){
        $html = getSimpleHTMLDOM(self::URI.'html/?q='.$this->getInput('u').$this->getInput('sort'))
            or returnServerError('Could not request DuckDuckGo.');

        foreach($html->find('div.results_links') as $element) {
                $item = array();
                $item['uri'] = $element->find('a', 0)->href;
                $item['title'] = $element->find('a', 1)->innertext;
                $item['content'] = $element->find('div.snippet', 0)->plaintext;
                $this->items[] = $item;
        }
    }
}
