<?php
/**
* RssBridgeAlias
* Returns the latest alias from http://alias.sh/latest-aliases/
*
* @name Alias
* @description Returns the last alias from Alias.sh
*/
class AliasBridge extends BridgeAbstract{

    public function collectData(array $param){
        $html = file_get_html('http://alias.sh/latest-aliases/') or $this->returnError('Could not request Alias.', 404);

        foreach($html->find('.views-row') as $element) {
            $item = new \Item();
            $item->uri = 'http://alias.sh/'.$element->find('a',0)->href;
            //$item->thumbnailUri = $element->find('img',0)->getAttribute('data-defer-src');
            $item->content = $element->find('.bash',0)->plaintext;
            $item->title = $element->find('a',0)->plaintext;
            $this->items[] = $item;
        }
    }

    public function getName(){
        return 'Alias latest';
    }

    public function getURI(){
        return 'http://alias.sh/';
    }

    public function getCacheDuration(){
        return 21600; // 6 hours
    }
}
