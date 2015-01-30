<?php
/**
* RssBridgeMsnMonde
* Returns the 10 newest posts from MSN Actualités (full text)
*
* @name MSN Actu Monde
* @homepage http://www.msn.com/fr-fr/actualite/monde
* @description Returns the 10 newest posts from MSN Actualités (full text)
* @maintainer kranack
* @update 2015-01-30
*/
class MsnMondeBridge extends BridgeAbstract{

    public function collectData(array $param){

    function MsnMondeExtractContent($url, &$item) {
      $html2 = file_get_html($url);
      $item->content = $html2->find('#content', 0)->find('article', 0)->find('section', 0)->plaintext;
      $item->timestamp = strtotime($html2->find('.authorinfo-txt', 0)->find('time', 0)->datetime);
    }

      $html = file_get_html('http://www.msn.com/fr-fr/actualite/monde') or $this->returnError('Could not request MsnMonde.', 404);
      $limit = 0;
      foreach($html->find('.smalla') as $article) {
       if($limit < 10) {
         $item = new \Item();
         $item->title = utf8_decode($article->find('h4', 0)->innertext);
         $item->uri = "http://www.msn.com" . utf8_decode($article->find('a', 0)->href);
         MsnMondeExtractContent($item->uri, $item);
         $this->items[] = $item;
         $limit++;
       }
      }
    }

    public function getName(){
        return 'MSN Actu Monde';
    }

    public function getURI(){
        return 'http://www.msn.com/fr-fr/actualite/monde';
    }

    public function getCacheDuration(){
        return 3600; // 1 hour
    }
}
