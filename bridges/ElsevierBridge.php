<?php
/**
 * ElsevierBridge
 *
 * @name Elsevier Bridge
 * @description Returns the recent articles published in Elsevier journals
 */
class ElsevierBridge extends BridgeAbstract{
  public function loadMetadatas() {

    $this->maintainer = 'Pierre Mazière';
    $this->name = 'Elsevier journals recent articles';
    $this->uri = 'http://www.journals.elsevier.com';
    $this->description = 'Returns the recent articles published in Elsevier journals';
    $this->update = '2016-06-26';

    $this->parameters=
      '[
         {
           "name" : "Journal name",
           "identifier" : "j"
         }
       ]';
  }

  public function collectData(array $param){
    $uri = 'http://www.journals.elsevier.com/'.$param['j'].'/recent-articles/';
    $html = file_get_html($uri)
      or $this->returnError('No results for Elsevier journal '.$param['j'], 404);

    foreach($html->find('.pod-listing') as $article){

      $item = new \Item();
      $item->uri=$article->find('.pod-listing-header>a',0)->getAttribute('href').'?np=y';
      $item->title=$article->find('.pod-listing-header>a',0)->plaintext;
      $item->name=trim($article->find('small',0)->plaintext);
      $item->timestamp=strtotime($article->find('.article-info',0)->plaintext);
      $item->content=trim($article->find('.article-content',0)->plaintext);

      $this->items[]=$item;
    }
  }

  public function getName(){
    return 'Elsevier journals recent articles';
  }

  public function getURI(){
    return 'http://www.journals.elsevier.com';
  }

  public function getCacheDuration(){
    return 43200; // 12h
  }
}
