<?php
/**
* RssBridgeCollegeDeFrance
* Returns the 10 newest posts from http://www.college-de-france.fr
*
* @name CollegeDeFrance
* @description Returns the 10 newest posts from CollegeDeFrance
*/
class CollegeDeFranceBridge extends BridgeAbstract{
    public function collectData(array $param){
        $html = file_get_html('http://www.college-de-france.fr/site/audio-video/_audiovideos.jsp?index=0&prompt=&fulltextdefault=mots-cles...&fulltext=mots-cles...&fields=TYPE2_ACTIVITY&fieldsdefault=0_0&TYPE2=0') or $this->returnError('Could not request CollegeDeFrance.', 404);
        $limit = 0;
        foreach($html->find('li.audio') as $element) {
         if($limit < 10) {
         $item = new \Item();
         $item->title = $element->find('span.title', 0)->plaintext;
         $item->timestamp = strtotime($element->find('span.date', 0)->plaintext);
         $item->content =  $element->find('span.lecturer', 0)->innertext . ' - ' . $element->find('span.title', 0)->innertext;
         $item->uri = $element->find('a', 0)->href;
         $this->items[] = $item;
         $limit++;
         }
        }

    }
    public function getName(){
        return 'CollegeDeFrance';
    }
    public function getURI(){
        return 'http://www.college-de-france.fr/';
    }
    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}
