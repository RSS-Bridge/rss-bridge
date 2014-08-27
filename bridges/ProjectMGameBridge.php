<?php
/**
* 2014-08-27
* @name Project M Game Bridge
* @homepage http://projectmgame.com/en/
* @description Returns the newest articles.
* @maintainer corenting
*/
class ProjectMGameBridge extends BridgeAbstract{
  public function collectData(array $param){
    $html = '';
    $html = file_get_html('http://projectmgame.com/en/') or $this->returnError('Error while downloading the Project M homepage', 404);

    foreach($html->find('article') as $article) {
      $item = new \Item();
      $item->uri = 'http://projectmgame.com/en/'.$article->find('section div.info_block a',0)->href;
      $item->title = $article->find('h1 p',0)->innertext;

      $p_list = $article->find('section p');
      $content = '';
      foreach($p_list as $p)
      {
        $content .= $p->innertext;
      }
      $item->content = $content;

      // get publication date
      $str_date = $article->find('section div.info_block a',0)->innertext;
      $timestamp = strtotime($str_date);
      $item->timestamp = $timestamp;

      $this->items[] = $item;
    }
  }

  public function getName(){
    return 'Project M Game Bridge';
  }

  public function getURI(){
    return 'http://projectmgame.com/en/';
  }

  public function getCacheDuration(){
    return 10800;
  }
}
