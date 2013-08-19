<?php
/**
* RssBridgeAlias
* Returns the most-used alias from http://alias.sh/latest-aliases/
*
* @name Alias
* @description Returns the most used alias from Alias.sh
*/
class AliasBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://alias.sh/most-popular/usage') or $this->returnError('Could not request Alias.', 404);
		$html = $html->find('#content',0);
        foreach($html->find('.views-row') as $element) {
            $item = new \Item();
            $item->uri = 'http://alias.sh/'.$element->find('a',0)->href;
            //$item->thumbnailUri = $element->find('img',0)->getAttribute('data-defer-src');
            $item->content = '<pre>'.$element->find('.bash',0)->outertext.'</pre>';
            $item->title = $element->find('a',0)->plaintext;
            $this->items[] = $item;
        }
    }

    public function getName(){
        return 'Alias most used';
    }

    public function getURI(){
        return 'http://alias.sh/';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
