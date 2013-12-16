<?php
/**
* RssBridgeDuckDuckGo
* Search DuckDuckGo for most recent pages regarding a specific topic.
* Returns the most recent links in results, sorting by date (most recent first).
*
* @name DuckDuckGo
* @description Returns most recent results from DuckDuckGo.
* @use1(u="keyword")
*/
class DuckDuckGoBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = '';
        $link = 'https://duckduckgo.com/html/?q='.$param[u].'+sort:date';

        $html = file_get_html($link) or $this->returnError('Could not request DuckDuckGo.', 404);

        foreach($html->find('div.results_links') as $element) {
                $item = new \Item();
                $item->uri = $element->find('a', 0)->href;
                $item->title = $element->find('a', 1)->innertext;
                $item->content = $element->find('div.snippet', 0)->plaintext;
                $this->items[] = $item;
        }
    }

    public function getName(){
        return 'DuckDuckGo';
    }

    public function getURI(){
        return 'https://duckduckgo.com';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
